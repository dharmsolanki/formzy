<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class MerchantController extends Controller
{
    /**
     * List all merchants.
     */
    public function index()
    {
        $merchants = User::role('merchant')->latest()->paginate(15);

        return view('admin.merchants.index', compact('merchants'));
    }

    /**
     * Show create merchant form.
     */
    public function create()
    {
        return view('admin.merchants.create');
    }

    /**
     * Store new merchant.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s\.]+$/'],
            'company_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'digits:10'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'name.regex' => 'Name must contain only letters.',
            'phone.digits' => 'Phone must be exactly 10 digits.',
        ]);

        $merchant = User::create([
            'name' => $validated['name'],
            'company_name' => $validated['company_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
        ]);

        $merchant->assignRole('merchant');

        return redirect()->route('admin.merchants.index')
            ->with('success', 'Merchant created successfully.');
    }
}
