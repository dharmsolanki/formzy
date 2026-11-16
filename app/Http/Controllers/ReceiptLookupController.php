<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\FormSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;

class ReceiptLookupController extends Controller
{
    // After 5 wrong tries for the same email (or the same mobile) the lookup waits 15 minutes
    private const MAX_FAILED = 5;
    private const LOCK_SECONDS = 900;

    /**
     * The empty Receipt tab.
     */
    public function show(string $uuid)
    {
        $form = Form::where('uuid', $uuid)->with('user')->firstOrFail();

        if (! $form->is_complete) {
            return $this->unavailable($form);
        }

        return view('public.receipt-lookup', [
            'form' => $form,
            'searched' => false,
            'results' => collect(),
            'email' => '',
            'mobile' => '',
        ]);
    }

    /**
     * Find the payments of one customer (email and mobile must both match).
     */
    public function find(Request $request, string $uuid)
    {
        $form = Form::where('uuid', $uuid)->with('user')->firstOrFail();

        if (! $form->is_complete) {
            return $this->unavailable($form);
        }

        $email = mb_strtolower(trim((string) $request->input('email')));
        $mobile = $this->normalizeMobile((string) $request->input('mobile'));

        $shown = [
            'form' => $form,
            'searched' => false,
            'results' => collect(),
            'email' => (string) $request->input('email'),
            'mobile' => (string) $request->input('mobile'),
        ];

        $validator = Validator::make(
            ['email' => $email, 'mobile' => $mobile],
            [
                'email' => ['required', 'email', 'max:255'],
                'mobile' => ['required', 'regex:/^[6-9][0-9]{9}$/'],
            ],
            ['mobile.regex' => 'Enter a valid 10-digit mobile number.']
        );

        if ($validator->fails()) {
            return view('public.receipt-lookup', $shown)->withErrors($validator);
        }

        // Wrong guesses are counted per email and per mobile number
        $keys = [
            'receipt-lookup:' . $form->id . ':e:' . md5($email),
            'receipt-lookup:' . $form->id . ':m:' . $mobile,
        ];

        foreach ($keys as $key) {
            if (RateLimiter::tooManyAttempts($key, self::MAX_FAILED)) {
                $minutes = (int) ceil(RateLimiter::availableIn($key) / 60);

                return view('public.receipt-lookup', $shown)->withErrors([
                    'lookup' => 'Too many attempts. Please try again after ' . max($minutes, 1) . ' minute(s).',
                ]);
            }
        }

        // Only this form, and only a record where both email and mobile match
        $results = FormSubmission::where('form_id', $form->id)
            ->where('customer_email', $email)
            ->where('customer_mobile', $mobile)
            ->latest()
            ->limit(20)
            ->get(['id', 'uuid', 'amount', 'payment_status', 'created_at']);

        if ($results->isEmpty()) {
            foreach ($keys as $key) {
                RateLimiter::hit($key, self::LOCK_SECONDS);
            }
        } else {
            foreach ($keys as $key) {
                RateLimiter::clear($key);
            }
        }

        return view('public.receipt-lookup', array_merge($shown, [
            'searched' => true,
            'results' => $results,
        ]));
    }

    private function unavailable(Form $form)
    {
        return view('public.form-unavailable', [
            'message' => 'This form is not available yet.',
            'merchant' => $form->user,
        ]);
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