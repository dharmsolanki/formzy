<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\FormField;
use App\Models\FormPaymentConfig;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class FormController extends Controller
{
    public function index()
    {
        $forms = Form::with('user')->latest()->paginate(15);

        return view('admin.forms.index', compact('forms'));
    }

    /* ---------- STEP 1: Basic info + fields ---------- */

    public function create()
    {
        $merchants = User::role('merchant')->orderBy('company_name')->get();

        return view('admin.forms.create', compact('merchants'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateForm($request);

        $form = DB::transaction(function () use ($validated) {
            $form = Form::create([
                'user_id' => $validated['user_id'],
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'amount_type' => $validated['amount_type'],
                'fixed_amount' => $validated['amount_type'] === 'fixed' ? $validated['fixed_amount'] : null,
                'is_active' => true,
                'expires_at' => $validated['expires_at'] ?? null,
                'wizard_step' => 2,
                'is_complete' => false,
            ]);

            $this->saveFields($form, $validated['fields']);

            return $form;
        });

        return redirect()->route('admin.forms.payment', $form)
            ->with('success', 'Step 1 saved. Now configure payment.');
    }

    public function edit(Form $form)
    {
        $merchants = User::role('merchant')->orderBy('company_name')->get();
        $form->load('fields');

        return view('admin.forms.edit', compact('form', 'merchants'));
    }

    public function update(Request $request, Form $form)
    {
        $validated = $this->validateForm($request);

        DB::transaction(function () use ($validated, $form) {
            $data = [
                'user_id' => $validated['user_id'],
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'amount_type' => $validated['amount_type'],
                'fixed_amount' => $validated['amount_type'] === 'fixed' ? $validated['fixed_amount'] : null,
                'expires_at' => $validated['expires_at'] ?? null,
            ];

            if (! $form->is_complete) {
                $data['wizard_step'] = max($form->wizard_step, 2);
            }

            $form->update($data);

            $form->fields()->delete();
            $this->saveFields($form, $validated['fields']);
        });

        return redirect()->route('admin.forms.payment', $form)
            ->with('success', 'Step 1 updated. Continue with payment configuration.');
    }

    /* ---------- STEP 2: Payment configuration (per form) ---------- */

    public function paymentEdit(Form $form)
    {
        $form->load('paymentConfig');

        return view('admin.forms.payment', compact('form'));
    }

    public function paymentStore(Request $request, Form $form)
    {
        $form->load('paymentConfig');
        $existing = $form->paymentConfig;
        $hasConfig = $existing && $existing->is_verified;

        $validated = $request->validate([
            'razorpay_key_id' => [$hasConfig ? 'nullable' : 'required', 'string', 'starts_with:rzp_'],
            'razorpay_key_secret' => ['nullable', 'string', 'min:20', $hasConfig ? 'nullable' : 'required'],
            'webhook_secret' => ['nullable', 'string', 'min:8', 'max:255'],
        ]);

        $newWebhookSecret = $validated['webhook_secret'] ?? null;

        $newKeyId = $validated['razorpay_key_id'] ?? null;
        $newSecret = $validated['razorpay_key_secret'] ?? null;

        // Keep existing credentials: secret empty and key id unchanged/empty
        if ($hasConfig && empty($newSecret)) {
            if (! empty($newKeyId) && $newKeyId !== $existing->razorpay_key_id) {
                return back()
                    ->withErrors(['razorpay_key_secret' => 'Key ID change ki hai to Key Secret bhi daalo.'])
                    ->withInput();
            }

            if (! empty($newWebhookSecret)) {
                $existing->update(['webhook_secret' => $newWebhookSecret]);
            }

            return redirect()->route('admin.forms.receipt', $form)
                ->with('success', 'Existing credentials kept.');
        }

        if (empty($newKeyId)) {
            return back()
                ->withErrors(['razorpay_key_id' => 'Key ID required.'])
                ->withInput($request->except('razorpay_key_secret'));
        }

        try {
            $response = Http::timeout(15)
                ->withBasicAuth($validated['razorpay_key_id'], $validated['razorpay_key_secret'])
                ->get('https://api.razorpay.com/v1/payments', ['count' => 1]);
        } catch (ConnectionException $e) {
            return back()
                ->withErrors(['razorpay_key_secret' => 'Could not reach Razorpay. Check internet and try again.'])
                ->withInput($request->except('razorpay_key_secret'));
        }

        if ($response->status() === 401) {
            return back()
                ->withErrors(['razorpay_key_secret' => 'Invalid Razorpay credentials. Check Key ID and Key Secret.'])
                ->withInput($request->except('razorpay_key_secret'));
        }

        if (! $response->successful()) {
            return back()
                ->withErrors(['razorpay_key_secret' => 'Could not verify credentials with Razorpay. Try again.'])
                ->withInput($request->except('razorpay_key_secret'));
        }

        $configData = [
            'razorpay_key_id' => $validated['razorpay_key_id'],
            'razorpay_key_secret' => $validated['razorpay_key_secret'],
            'is_verified' => true,
        ];

        if (! empty($newWebhookSecret)) {
            $configData['webhook_secret'] = $newWebhookSecret;
        }

        FormPaymentConfig::updateOrCreate(
            ['form_id' => $form->id],
            $configData
        );

        if (! $form->is_complete) {
            $form->update(['wizard_step' => max($form->wizard_step, 3)]);
        }

        return redirect()->route('admin.forms.receipt', $form)
            ->with('success', 'Step 2 saved. Credentials verified.');
    }

    /* ---------- STEP 3: Receipt / success message + finish ---------- */

    public function receiptEdit(Form $form)
    {
        if (! $form->paymentConfig || ! $form->paymentConfig->is_verified) {
            return redirect()->route('admin.forms.payment', $form)
                ->withErrors(['razorpay_key_id' => 'Complete payment configuration first.']);
        }

        return view('admin.forms.receipt', compact('form'));
    }

    public function receiptStore(Request $request, Form $form)
    {
        $validated = $request->validate([
            'success_message' => ['nullable', 'string', 'max:1000'],
        ]);

        if (! $form->paymentConfig || ! $form->paymentConfig->is_verified) {
            return redirect()->route('admin.forms.payment', $form)
                ->withErrors(['razorpay_key_id' => 'Complete payment configuration first.']);
        }

        $form->update([
            'success_message' => $validated['success_message'] ?? null,
            'wizard_step' => 3,
            'is_complete' => true,
        ]);

        return redirect()->route('admin.forms.index')
            ->with('success', 'Form is complete and live.');
    }

    /* ---------- Other actions ---------- */

    public function destroy(Form $form)
    {
        $form->delete();

        return redirect()->route('admin.forms.index')
            ->with('success', 'Form deleted successfully.');
    }

    public function toggleStatus(Form $form)
    {
        if (! $form->is_complete) {
            return back()->withErrors(['form' => 'Finish the form setup before activating it.']);
        }

        $form->update(['is_active' => ! $form->is_active]);

        return back()->with('success', 'Form status updated.');
    }

    /* ---------- Helpers ---------- */

    private function validateForm(Request $request): array
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'amount_type' => ['required', 'in:fixed,customer_entered'],
            'fixed_amount' => ['required_if:amount_type,fixed', 'nullable', 'numeric', 'min:1'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'fields' => ['required', 'array', 'min:1'],
            'fields.*.label' => ['required', 'string', 'max:255'],
            'fields.*.type' => ['required', 'in:text,email,number,dropdown,radio,checkbox'],
            'fields.*.options' => ['nullable', 'string'],
            'fields.*.is_required' => ['nullable'],
            'fields.*.is_readonly' => ['nullable'],
        ]);

        $merchant = User::findOrFail($validated['user_id']);
        if (! $merchant->hasRole('merchant')) {
            abort(422, 'Selected user is not a valid merchant.');
        }

        return $validated;
    }

    private function saveFields(Form $form, array $fields): void
    {
        foreach (array_values($fields) as $index => $fieldData) {
            $options = null;

            if (in_array($fieldData['type'], ['dropdown', 'radio', 'checkbox']) && ! empty($fieldData['options'])) {
                $options = array_map('trim', explode(',', $fieldData['options']));
            }

            FormField::create([
                'form_id' => $form->id,
                'label' => $fieldData['label'],
                'type' => $fieldData['type'],
                'options' => $options,
                'is_required' => isset($fieldData['is_required']),
                'is_readonly' => isset($fieldData['is_readonly']),
                'order' => $index,
            ]);
        }
    }
}