<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\FormSubmission;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Razorpay\Api\Api;

class FormSubmissionController extends Controller
{
    /**
     * Show the public form.
     */
    public function show(string $uuid)
    {
        $form = Form::where('uuid', $uuid)->with('fields')->firstOrFail();

        if (! $form->is_complete) {
            return view('public.form-unavailable', ['message' => 'This form is not available yet.']);
        }

        if (! $form->is_active) {
            return view('public.form-unavailable', ['message' => 'This form is no longer accepting responses.']);
        }

        if ($form->expires_at && $form->expires_at->isPast()) {
            return view('public.form-unavailable', ['message' => 'Registration for this form has closed.']);
        }

        return view('public.form-show', compact('form'));
    }

    /**
     * Handle form submission.
     */
    public function store(Request $request, string $uuid)
    {
        $form = Form::where('uuid', $uuid)->with(['fields', 'paymentConfig'])->firstOrFail();

        if (! $form->is_complete
            || ! $form->is_active
            || ($form->expires_at && $form->expires_at->isPast())) {
            return back()->withErrors(['form' => 'This form is no longer accepting responses.']);
        }

        $config = $form->paymentConfig;

        if (! $config || ! $config->is_verified) {
            return back()->withErrors(['form' => 'Payments are not configured for this form. Please contact the organizer.']);
        }

        // Build dynamic validation rules based on this form's fields
        $rules = [];
        foreach ($form->fields as $field) {
            $fieldRules = [];

            $fieldRules[] = $field->is_required ? 'required' : 'nullable';

            if ($field->type === 'email') {
                $fieldRules[] = 'email';
            } elseif ($field->type === 'number') {
                $fieldRules[] = 'numeric';
            } elseif (in_array($field->type, ['dropdown', 'radio'])) {
                $fieldRules[] = Rule::in($field->options ?? []);
            }

            $rules["fields.{$field->id}"] = $fieldRules;
        }

        if ($form->amount_type === 'customer_entered') {
            $rules['custom_amount'] = ['required', 'numeric', 'min:1'];
        }

        $attributes = [];
        foreach ($form->fields as $field) {
            $attributes["fields.{$field->id}"] = $field->label;
        }
        $attributes['custom_amount'] = 'amount';

        $validated = $request->validate($rules, [], $attributes);

        // Map field IDs back to labels for readable storage
        $submissionData = [];
        foreach ($form->fields as $field) {
            $submissionData[$field->label] = $validated['fields'][$field->id] ?? null;
        }

        $amount = $form->amount_type === 'fixed'
            ? $form->fixed_amount
            : $validated['custom_amount'];

        $submission = FormSubmission::create([
            'form_id' => $form->id,
            'data' => $submissionData,
            'amount' => $amount,
            'payment_status' => 'pending',
        ]);

        $api = new Api($config->razorpay_key_id, $config->razorpay_key_secret);

        $razorpayOrder = $api->order->create([
            'receipt' => $submission->uuid,
            'amount' => (int) round($submission->amount * 100), // paise
            'currency' => 'INR',
            'notes' => [
                'form_submission_id' => $submission->id,
            ],
        ]);

        $submission->update(['razorpay_order_id' => $razorpayOrder->id]);

        return view('public.form-checkout', [
            'submission' => $submission,
            'form' => $form,
            'razorpayOrderId' => $razorpayOrder->id,
            'razorpayKeyId' => $config->razorpay_key_id,
        ]);
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
}