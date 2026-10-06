<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\FormField;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FormController extends Controller
{
    /**
     * List all forms.
     */
    public function index()
    {
        $forms = Form::with('user')->latest()->paginate(15);

        return view('admin.forms.index', compact('forms'));
    }

    /**
     * Show create form page.
     */
    public function create()
    {
        $merchants = User::role('merchant')->orderBy('company_name')->get();

        return view('admin.forms.create', compact('merchants'));
    }

    /**
     * Store new form with fields.
     */
    public function store(Request $request)
    {
        $validated = $this->validateForm($request);

        DB::transaction(function () use ($validated) {
            $form = Form::create([
                'user_id' => $validated['user_id'],
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'amount_type' => $validated['amount_type'],
                'fixed_amount' => $validated['amount_type'] === 'fixed' ? $validated['fixed_amount'] : null,
                'is_active' => true,
                'expires_at' => $validated['expires_at'] ?? null,
            ]);

            $this->saveFields($form, $validated['fields']);
        });

        return redirect()->route('admin.forms.index')
            ->with('success', 'Form created successfully.');
    }

    /**
     * Show edit form page.
     */
    public function edit(Form $form)
    {
        $merchants = User::role('merchant')->orderBy('company_name')->get();
        $form->load('fields');

        return view('admin.forms.edit', compact('form', 'merchants'));
    }

    /**
     * Update existing form and its fields.
     */
    public function update(Request $request, Form $form)
    {
        $validated = $this->validateForm($request);

        DB::transaction(function () use ($validated, $form) {
            $form->update([
                'user_id' => $validated['user_id'],
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'amount_type' => $validated['amount_type'],
                'fixed_amount' => $validated['amount_type'] === 'fixed' ? $validated['fixed_amount'] : null,
                'expires_at' => $validated['expires_at'] ?? null,
            ]);

            // Remove old fields, re-create with new set (simplest for v1, avoids complex diffing)
            $form->fields()->delete();
            $this->saveFields($form, $validated['fields']);
        });

        return redirect()->route('admin.forms.index')
            ->with('success', 'Form updated successfully.');
    }

    /**
     * Delete a form.
     */
    public function destroy(Form $form)
    {
        $form->delete();

        return redirect()->route('admin.forms.index')
            ->with('success', 'Form deleted successfully.');
    }

    /**
     * Toggle active/inactive status.
     */
    public function toggleStatus(Form $form)
    {
        $form->update(['is_active' => ! $form->is_active]);

        return back()->with('success', 'Form status updated.');
    }

    /**
     * Shared validation rules for store/update.
     */
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

        // Confirm the selected user actually has the 'merchant' role (defense against tampered payloads)
        $merchant = User::findOrFail($validated['user_id']);
        if (! $merchant->hasRole('merchant')) {
            abort(422, 'Selected user is not a valid merchant.');
        }

        return $validated;
    }

    /**
     * Persist field rows for a form.
     */
    private function saveFields(Form $form, array $fields): void
    {
        foreach ($fields as $index => $fieldData) {
            $options = null;

            if (in_array($fieldData['type'], ['dropdown', 'radio', 'checkbox']) && ! empty($fieldData['options'])) {
                // Options submitted as comma-separated text, convert to array
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