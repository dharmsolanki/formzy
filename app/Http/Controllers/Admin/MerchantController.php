<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class MerchantController extends Controller
{
    /**
     * List all merchants.
     */
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q'      => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);

        $query = User::role('merchant');

        if (! empty($filters['q'])) {
            $term = '%' . addcslashes($filters['q'], '%_\\') . '%';

            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('company_name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('phone', 'like', $term);
            });
        }

        if (($filters['status'] ?? '') === 'active') {
            $query->where('is_active', true);
        } elseif (($filters['status'] ?? '') === 'inactive') {
            $query->where('is_active', false);
        }

        $merchants = $query->latest()->paginate(15)->withQueryString();

        return view('admin.merchants.index', compact('merchants', 'filters'));
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

    /**
     * Show reset password form for a merchant.
     */
    public function passwordEdit(User $merchant)
    {
        abort_unless($merchant->hasRole('merchant'), 404);

        return view('admin.merchants.password', compact('merchant'));
    }

    /**
     * Set a new password for a merchant.
     */
    public function passwordUpdate(Request $request, User $merchant)
    {
        abort_unless($merchant->hasRole('merchant'), 404);

        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $merchant->forceFill([
            'password' => Hash::make($validated['password']),
            'remember_token' => Str::random(60),
        ])->save();

        // Log the merchant out of all devices (sessions are stored in DB)
        DB::table('sessions')->where('user_id', $merchant->id)->delete();

        return redirect()->route('admin.merchants.index')
            ->with('success', 'Password updated for ' . $merchant->company_name . '.');
    }

    /**
     * Show edit merchant form.
     */
    public function edit(User $merchant)
    {
        abort_unless($merchant->hasRole('merchant'), 404);

        return view('admin.merchants.edit', compact('merchant'));
    }

    /**
     * Update merchant details and logo.
     */
    public function update(Request $request, User $merchant)
    {
        abort_unless($merchant->hasRole('merchant'), 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s\.]+$/'],
            'company_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($merchant->id)],
            'phone' => ['required', 'digits:10'],
            'logo' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:1024', 'dimensions:max_width=2000,max_height=2000'],
        ], [
            'name.regex' => 'Name must contain only letters.',
            'phone.digits' => 'Phone must be exactly 10 digits.',
            'logo.mimes' => 'Logo must be a PNG, JPG or WebP image.',
            'logo.max' => 'Logo must not be larger than 1 MB.',
            'logo.dimensions' => 'Logo must not be larger than 2000 x 2000 pixels.',
        ]);

        $data = [
            'name' => $validated['name'],
            'company_name' => $validated['company_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'is_active' => $request->boolean('is_active'),
        ];

        if ($request->hasFile('logo')) {
            // Random file name is generated by Laravel; original name is never used
            $data['logo_path'] = $request->file('logo')->store('logos', 'public');

            if ($merchant->logo_path) {
                Storage::disk('public')->delete($merchant->logo_path);
            }
        } elseif ($request->boolean('remove_logo') && $merchant->logo_path) {
            Storage::disk('public')->delete($merchant->logo_path);
            $data['logo_path'] = null;
        }

        $merchant->update($data);

        return redirect()->route('admin.merchants.index')
            ->with('success', 'Merchant updated successfully.');
    }
}
