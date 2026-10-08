<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\FormSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RazorpayWebhookController extends Controller
{
    public function handle(Request $request, string $uuid)
    {
        $form = Form::where('uuid', $uuid)->with('paymentConfig')->first();

        if (! $form || ! $form->paymentConfig || empty($form->paymentConfig->webhook_secret)) {
            // Do not reveal whether the form exists
            return response()->json(['status' => 'ignored'], 404);
        }

        $payload = $request->getContent();
        $signature = $request->header('X-Razorpay-Signature');

        if (empty($signature)) {
            return response()->json(['status' => 'invalid signature'], 400);
        }

        $expected = hash_hmac('sha256', $payload, $form->paymentConfig->webhook_secret);

        if (! hash_equals($expected, $signature)) {
            Log::warning('Razorpay webhook signature mismatch', ['form_id' => $form->id]);

            return response()->json(['status' => 'invalid signature'], 400);
        }

        $event = $request->input('event');

        if ($event === 'payment.captured') {
            $payment = $request->input('payload.payment.entity');
            $orderId = $payment['order_id'] ?? null;
            $paymentId = $payment['id'] ?? null;

            if ($orderId && $paymentId) {
                $submission = FormSubmission::where('form_id', $form->id)
                    ->where('razorpay_order_id', $orderId)
                    ->first();

                // Idempotent: skip if already paid
                if ($submission && $submission->payment_status !== 'paid') {
                    $submission->update([
                        'payment_status' => 'paid',
                        'razorpay_payment_id' => $paymentId,
                    ]);
                }
            }
        }

        if ($event === 'payment.failed') {
            $payment = $request->input('payload.payment.entity');
            $orderId = $payment['order_id'] ?? null;

            if ($orderId) {
                // Never downgrade a paid submission
                FormSubmission::where('form_id', $form->id)
                    ->where('razorpay_order_id', $orderId)
                    ->where('payment_status', 'pending')
                    ->update(['payment_status' => 'failed']);
            }
        }

        return response()->json(['status' => 'ok']);
    }
}