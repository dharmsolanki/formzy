<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\FormSubmission;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\MerchantCredential;
use Razorpay\Api\Api;

class FormSubmissionController extends Controller
{
    /**
     * Show the public form.
     */
    public function show(string $uuid)
    {
        $form = Form::where('uuid', $uuid)->with('fields')->firstOrFail();

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
        $form = Form::where('uuid', $uuid)->with('fields')->firstOrFail();

        if (! $form->is_active || ($form->expires_at && $form->expires_at->isPast())) {
            return back()->withErrors(['form' => 'This form is no longer accepting responses.']);
        }

        // Build dynamic validation rules based on this form's fields
        $rules = [];
        foreach ($form->fields as $field) {
            $fieldRules = [];

            if ($field->is_required) {
                $fieldRules[] = 'required';
            } else {
                $fieldRules[] = 'nullable';
            }

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

        $validated = $request->validate($rules);

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

        $credential = MerchantCredential::where('user_id', $form->user_id)->first();

        if (! $credential || ! $credential->is_verified) {
            return back()->withErrors(['form' => 'This merchant has not configured payments yet. Please contact the organizer.']);
        }

        $api = new Api($credential->razorpay_key_id, $credential->razorpay_key_secret);

        $razorpayOrder = $api->order->create([
            'receipt' => $submission->uuid,
            'amount' => (int) round($submission->amount * 100), // amount in paise
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
            'razorpayKeyId' => $credential->razorpay_key_id,
        ]);
    }

    /**
     * Verify Razorpay payment signature and mark submission as paid.
     */
    public function paymentCallback(Request $request, string $uuid)
    {
        $submission = FormSubmission::where('uuid', $uuid)->with('form')->firstOrFail();

        $credential = MerchantCredential::where('user_id', $submission->form->user_id)->first();

        if (! $credential) {
            abort(404);
        }

        $api = new Api($credential->razorpay_key_id, $credential->razorpay_key_secret);

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

        // Signature valid — also confirm the order_id matches what we created (defense in depth)
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
