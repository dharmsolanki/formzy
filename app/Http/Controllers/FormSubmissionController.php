<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\FormField;
use App\Models\FormSubmission;
use Illuminate\Http\Request;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Razorpay\Api\Api;
use Throwable;

class FormSubmissionController extends Controller
{
    /**
     * Show the public form.
     */
    public function show(string $uuid)
    {
        $form = Form::where('uuid', $uuid)->with(['fields', 'user'])->firstOrFail();

        if (! $form->is_complete) {
            return view('public.form-unavailable', [
                'message' => 'This form is not available yet.',
                'merchant' => $form->user,
            ]);
        }

        if (! $form->is_active || ! $form->user->is_active) {
            return view('public.form-unavailable', [
                'message' => 'This form is no longer accepting responses.',
                'merchant' => $form->user,
            ]);
        }

        if ($form->expires_at && $form->expires_at->isPast()) {
            return view('public.form-unavailable', [
                'message' => 'Registration for this form has closed.',
                'merchant' => $form->user,
            ]);
        }

        return view('public.form-show', compact('form'));
    }

    /**
     * Handle form submission.
     */
    public function store(Request $request, string $uuid)
    {
        $form = Form::where('uuid', $uuid)->with(['fields', 'paymentConfig', 'user'])->firstOrFail();

        if (
            ! $form->is_complete
            || ! $form->is_active
            || ! $form->user->is_active
            || ($form->expires_at && $form->expires_at->isPast())
        ) {
            return back()->withErrors(['form' => 'This form is no longer accepting responses.']);
        }

        // The admin changed the form after this page was opened
        if ((int) $request->input('fields_version') !== (int) $form->fields_version) {
            return redirect()->route('form.public', $form->uuid)
                ->withInput($request->except('fields_version'))
                ->withErrors(['form' => 'This form was updated. Please check the details and submit again.']);
        }

        $config = $form->paymentConfig;

        if (! $config || ! $config->is_verified) {
            return back()->withErrors(['form' => 'Payments are not configured for this form. Please contact the organizer.']);
        }

        // Clean the two compulsory fields before validating
        $input = (array) $request->input('fields', []);

        foreach ($form->fields as $field) {
            $key = $field->key;

            if ($field->is_system && isset($input[$key]) && is_string($input[$key])) {
                if ($key === FormField::KEY_EMAIL) {
                    $input[$key] = mb_strtolower(trim($input[$key]));
                }
                if ($key === FormField::KEY_MOBILE) {
                    $input[$key] = $this->normalizeMobile($input[$key]);
                }
            }
        }

        $request->merge(['fields' => $input]);

        // Build the rules from this form's fields (by key)
        $rules = [];
        $attributes = [];

        foreach ($form->fields as $field) {
            $name = "fields.{$field->key}";
            $attributes[$name] = $field->label;

            if ($field->is_system && $field->key === FormField::KEY_EMAIL) {
                $rules[$name] = ['required', 'string', 'email', 'max:255'];
                continue;
            }

            if ($field->is_system && $field->key === FormField::KEY_MOBILE) {
                $rules[$name] = ['required', 'regex:/^[6-9][0-9]{9}$/'];
                continue;
            }

            $fieldRules = [$field->is_required ? 'required' : 'nullable'];

            switch ($field->type) {
                case 'email':
                    $fieldRules[] = 'email';
                    $fieldRules[] = 'max:255';
                    break;
                case 'number':
                    $fieldRules[] = 'numeric';
                    break;
                case 'dropdown':
                case 'radio':
                    $fieldRules[] = Rule::in($field->options ?? []);
                    break;
                case 'checkbox':
                    $fieldRules[] = 'array';
                    $rules[$name . '.*'] = [Rule::in($field->options ?? [])];
                    break;
                default:
                    $fieldRules[] = 'string';
                    $fieldRules[] = 'max:1000';
            }

            $rules[$name] = $fieldRules;
        }

        if ($form->amount_type === 'customer_entered') {
            $rules['custom_amount'] = ['required', 'numeric', 'min:1', 'max:500000', 'decimal:0,2'];
        }
        $attributes['custom_amount'] = 'amount';

        $validated = $request->validate($rules, [
            'fields.mobile.regex' => 'Enter a valid 10-digit mobile number.',
            'custom_amount.min' => 'The amount must be at least 1.',
            'custom_amount.max' => 'The amount must not be more than 5,00,000.',
            'custom_amount.decimal' => 'The amount can have at most 2 decimal places.',
        ], $attributes);

        // Answers by key, and the labels the customer saw
        $answers = [];
        $labels = [];

        foreach ($form->fields as $field) {
            $answers[$field->key] = $validated['fields'][$field->key] ?? null;
            $labels[$field->key] = $field->label;
        }

        $amount = $form->amount_type === 'fixed'
            ? $form->fixed_amount
            : round((float) $validated['custom_amount'], 2);

        $email = $answers[FormField::KEY_EMAIL] ?? null;
        $mobile = $answers[FormField::KEY_MOBILE] ?? null;

        // One request at a time per customer, so a quick double click cannot make two records
        $lock = Cache::lock('submit:' . $form->id . ':' . md5($email . '|' . $mobile), 15);

        try {
            $lock->block(5);
        } catch (LockTimeoutException $e) {
            return back()->withInput()
                ->withErrors(['form' => 'Your request is already being processed. Please wait a moment.']);
        }

        try {
            $submission = $this->reusablePending($form, $answers, $amount, $email, $mobile);

            if ($submission) {
                // Same customer, same answers, nothing paid yet: open the same order again
                $orderId = $submission->razorpay_order_id;
            } else {
                $submission = FormSubmission::create([
                    'form_id' => $form->id,
                    'data' => $answers,
                    'field_labels' => $labels,
                    'customer_email' => $email,
                    'customer_mobile' => $mobile,
                    'amount' => $amount,
                    'payment_status' => 'pending',
                ]);

                try {
                    $api = new Api($config->razorpay_key_id, $config->razorpay_key_secret);

                    $razorpayOrder = $api->order->create([
                        'receipt' => $submission->uuid,
                        'amount' => (int) round($submission->amount * 100), // paise
                        'currency' => 'INR',
                        'notes' => [
                            'form_submission_id' => $submission->id,
                        ],
                    ]);
                } catch (Throwable $e) {
                    Log::error('razorpay_order_create_failed', [
                        'form_id' => $form->id,
                        'message' => $e->getMessage(),
                    ]);

                    // No order means no payment, so do not leave an empty record behind
                    $submission->delete();

                    return back()->withInput()
                        ->withErrors(['form' => 'We could not start the payment. Please try again in a moment.']);
                }

                $submission->update(['razorpay_order_id' => $razorpayOrder->id]);
                $orderId = $razorpayOrder->id;
            }
        } finally {
            $lock->release();
        }

        return view('public.form-checkout', [
            'submission' => $submission,
            'form' => $form,
            'razorpayOrderId' => $orderId,
            'razorpayKeyId' => $config->razorpay_key_id,
        ]);
    }

    /**
     * A recent unpaid record of the same customer with exactly the same answers and amount.
     */
    private function reusablePending(Form $form, array $answers, $amount, ?string $email, ?string $mobile): ?FormSubmission
    {
        if (! $email || ! $mobile) {
            return null;
        }

        $candidates = FormSubmission::where('form_id', $form->id)
            ->where('payment_status', 'pending')
            ->where('customer_email', $email)
            ->where('customer_mobile', $mobile)
            ->whereNotNull('razorpay_order_id')
            ->where('created_at', '>=', now()->subMinutes(30))
            ->latest()
            ->get();

        foreach ($candidates as $c) {
            $sameAmount = abs((float) $c->amount - (float) $amount) < 0.001;

            if ($sameAmount && $this->sameAnswers($c->data ?? [], $answers)) {
                return $c;
            }
        }

        return null;
    }

    private function sameAnswers(array $a, array $b): bool
    {
        ksort($a);
        ksort($b);

        return json_encode($a) === json_encode($b);
    }

    /**
     * Verify Razorpay payment signature and mark submission as paid.
     */
    public function paymentCallback(Request $request, string $uuid)
    {
        $submission = FormSubmission::where('uuid', $uuid)->with('form.paymentConfig')->firstOrFail();

        $config = $submission->form->paymentConfig;

        if (! $config) {
            abort(404);
        }

        $api = new Api($config->razorpay_key_id, $config->razorpay_key_secret);

        $attributes = [
            'razorpay_order_id' => $request->input('razorpay_order_id'),
            'razorpay_payment_id' => $request->input('razorpay_payment_id'),
            'razorpay_signature' => $request->input('razorpay_signature'),
        ];

        try {
            $api->utility->verifyPaymentSignature($attributes);
        } catch (\Exception $e) {
            return view('public.payment-failed', compact('submission'));
        }

        // Order id must match the one we created for this submission
        if ($submission->razorpay_order_id !== $attributes['razorpay_order_id']) {
            return view('public.payment-failed', compact('submission'));
        }

        $submission->update([
            'payment_status' => 'paid',
            'razorpay_payment_id' => $attributes['razorpay_payment_id'],
        ]);

        return view('public.payment-success', compact('submission'));
    }

    /**
     * Someone opened the payment-callback address directly (Back button, old link).
     * Send them to the form page, never show a "method not allowed" error.
     */
    public function callbackRedirect(string $uuid)
    {
        // A reference (submission uuid): go to the form of that customer
        $submission = FormSubmission::where('uuid', $uuid)->with('form')->first();

        if ($submission && $submission->form) {
            return redirect()->route('form.public', $submission->form->uuid);
        }

        // A form uuid: go to that form
        $form = Form::where('uuid', $uuid)->first();

        abort_unless($form, 404);

        return redirect()->route('form.public', $form->uuid);
    }

    /**
     * Printable receipt. Only for paid submissions.
     */
    public function receipt(string $uuid)
    {
        $submission = FormSubmission::where('uuid', $uuid)
            ->where('payment_status', 'paid')
            ->with('form.user', 'form.fields')
            ->firstOrFail();

        return view('public.receipt', compact('submission'));
    }

    /**
     * "+91 98765-43210", "09876543210" and "9876543210" all become 9876543210.
     */
    private function normalizeMobile(string $value): string
    {
        $digits = preg_replace('/\D/', '', $value);

        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            return substr($digits, 2);
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            return substr($digits, 1);
        }

        return $digits;
    }
}
