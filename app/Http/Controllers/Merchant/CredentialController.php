<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\MerchantCredential;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CredentialController extends Controller
{
    /**
     * Show the credential form.
     */
    public function edit()
    {
        $credential = auth()->user()->merchantCredential;

        return view('merchant.credentials.edit', compact('credential'));
    }

    /**
     * Store or update merchant's Razorpay credentials.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'razorpay_key_id' => ['required', 'string', 'starts_with:rzp_'],
            'razorpay_key_secret' => ['required', 'string', 'min:20'],
        ]);

        // Test the credentials against Razorpay API before saving
        $response = Http::withBasicAuth(
            $validated['razorpay_key_id'],
            $validated['razorpay_key_secret']
        )->get('https://api.razorpay.com/v1/payments', ['count' => 1]);

        if ($response->status() === 401) {
            return back()
                ->withErrors(['razorpay_key_secret' => 'Invalid Razorpay credentials. Please check your Key ID and Key Secret.'])
                ->withInput($request->except(['razorpay_key_secret']));
        }

        if (! $response->successful()) {
            return back()
                ->withErrors(['razorpay_key_secret' => 'Could not verify credentials with Razorpay. Please try again.'])
                ->withInput($request->except(['razorpay_key_secret']));
        }

        MerchantCredential::updateOrCreate(
            ['user_id' => auth()->id()],
            [
                'razorpay_key_id' => $validated['razorpay_key_id'],
                'razorpay_key_secret' => $validated['razorpay_key_secret'],
                'is_verified' => true,
            ]
        );

        return redirect()->route('merchant.credentials.edit')
            ->with('success', 'Razorpay credentials verified and saved successfully.');
    }
}