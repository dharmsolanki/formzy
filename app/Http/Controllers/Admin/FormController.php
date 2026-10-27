<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Form;
use App\Models\FormField;
use App\Models\FormPaymentConfig;
use App\Models\FormSubmission;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class FormController extends Controller
{
    private const SYSTEM_KEYS = [FormField::KEY_EMAIL, FormField::KEY_MOBILE];
    private const RESERVED_LABELS = ['email', 'mobile number', 'mobile'];
    private const OPTION_TYPES = ['dropdown', 'radio', 'checkbox'];

    public function index(Request $request)
    {
        $filters = $request->validate([
            'merchant_id' => ['nullable', 'integer'],
            'form_id'     => ['nullable', 'integer'],
            'q'           => ['nullable', 'string', 'max:100'],
            'status'      => ['nullable', 'in:live,draft,inactive'],
        ]);

        $merchants = User::role('merchant')->orderBy('company_name')->get(['id', 'company_name']);

        if (! empty($filters['merchant_id'])) {
            abort_unless($merchants->contains('id', (int) $filters['merchant_id']), 404);
        }

        // Form filter works only after a merchant is chosen
        if (empty($filters['merchant_id'])) {
            $filters['form_id'] = null;
        }

        $query = Form::with('user');

        if (! empty($filters['merchant_id'])) {
            $query->where('user_id', $filters['merchant_id']);
        }

        if (! empty($filters['form_id'])) {
            abort_unless(
                Form::where('id', $filters['form_id'])->where('user_id', $filters['merchant_id'])->exists(),
                404
            );
            $query->where('id', $filters['form_id']);
        }

        if (! empty($filters['q'])) {
            $term = '%' . addcslashes($filters['q'], '%_\\') . '%';
            $query->where('title', 'like', $term);
        }

        switch ($filters['status'] ?? '') {
            case 'live':
                $query->where('is_complete', true)->where('is_active', true);
                break;
            case 'draft':
                $query->where('is_complete', false);
                break;
            case 'inactive':
                $query->where('is_complete', true)->where('is_active', false);
                break;
        }

        $forms = $query->latest()->paginate(15)->withQueryString();

        return view('admin.forms.index', compact('forms', 'filters', 'merchants'));
    }

    /**
     * JSON list of one merchant's forms (for the dependent dropdown).
     */
    public function byMerchant(User $merchant)
    {
        abort_unless($merchant->hasRole('merchant'), 404);

        return response()->json(
            Form::where('user_id', $merchant->id)->orderBy('title')->get(['id', 'title'])
        );
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
        $rows = $this->parseFields($request, null);

        $form = DB::transaction(function () use ($validated, $rows) {
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

            $this->syncFields($form, $rows);

            return $form;
        });

        return redirect()->route('admin.forms.payment', $form)
            ->with('success', 'Step 1 saved. Now configure payment.');
    }

    public function edit(Form $form)
    {
        $merchants = User::role('merchant')->orderBy('company_name')->get();
        $form->load('fields');

        // How many customers have answered each custom field
        $answered = [];
        foreach ($form->fields as $field) {
            if (! $field->is_system) {
                $answered[$field->key] = $this->answeredCount($form->id, $field->key);
            }
        }

        return view('admin.forms.edit', compact('form', 'merchants', 'answered'));
    }

    public function update(Request $request, Form $form)
    {
        $validated = $this->validateForm($request);
        $rows = $this->parseFields($request, $form);

        DB::transaction(function () use ($validated, $form, $rows) {
            $before = $this->snapshot($form);

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

            $changes = $this->syncFields($form, $rows);

            // Open forms in customers' browsers become stale when the fields change
            if ($before !== $this->snapshot($form)) {
                $form->increment('fields_version');
            }

            if ($changes) {
                AuditLog::record('form.fields_changed', null, [
                    'form_id' => $form->id,
                    'changes' => $changes,
                ]);
            }
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
                    ->withErrors(['razorpay_key_secret' => 'If you change the Key ID, you must also enter the Key Secret.'])
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
                ->withErrors(['razorpay_key_id' => 'The Razorpay Key ID is required.'])
                ->withInput($request->except('razorpay_key_secret'));
        }

        try {
            $response = Http::timeout(15)
                ->withBasicAuth($newKeyId, $newSecret)
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
            'razorpay_key_id' => $newKeyId,
            'razorpay_key_secret' => $newSecret,
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

    /**
     * Validation for the form itself (not the fields).
     */
    private function validateForm(Request $request): array
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'amount_type' => ['required', 'in:fixed,customer_entered'],
            'fixed_amount' => ['required_if:amount_type,fixed', 'nullable', 'numeric', 'min:1', 'max:500000', 'decimal:0,2'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        $merchant = User::findOrFail($validated['user_id']);
        if (! $merchant->hasRole('merchant')) {
            abort(422, 'Selected user is not a valid merchant.');
        }

        return $validated;
    }

    /**
     * Read the submitted field rows and validate the custom ones.
     * System rows (Email, Mobile) only carry their key, nothing else is trusted.
     */
    private function parseFields(Request $request, ?Form $form): array
    {
        $request->validate([
            'fields' => ['nullable', 'array', 'max:50'],
        ]);

        $rows = [];

        foreach (array_values((array) $request->input('fields', [])) as $row) {
            $row = (array) $row;
            $choice = $row['label_change'] ?? null;

            $rows[] = [
                'key' => trim((string) ($row['key'] ?? '')),
                'label' => trim((string) ($row['label'] ?? '')),
                'type' => $row['type'] ?? null,
                'options' => $row['options'] ?? null,
                'is_required' => isset($row['is_required']),
                'is_readonly' => isset($row['is_readonly']),
                'label_change' => in_array($choice, ['wording', 'new'], true) ? $choice : null,
            ];
        }

        $custom = array_values(array_filter(
            $rows,
            fn ($r) => ! in_array($r['key'], self::SYSTEM_KEYS, true)
        ));

        $validator = Validator::make(['fields' => $custom], [
            'fields.*.label' => ['required', 'string', 'max:255'],
            'fields.*.type' => ['required', 'in:text,email,number,dropdown,radio,checkbox'],
            'fields.*.options' => ['nullable', 'string', 'max:2000'],
        ], [], [
            'fields.*.label' => 'field label',
            'fields.*.type' => 'field type',
            'fields.*.options' => 'field options',
        ]);

        $active = $form
            ? FormField::where('form_id', $form->id)->whereNull('archived_at')->get()->keyBy('key')
            : collect();

        $validator->after(function ($v) use ($custom, $form, $active) {
            $seen = [];

            foreach ($custom as $i => $r) {
                $name = mb_strtolower($r['label']);

                if (in_array($name, self::RESERVED_LABELS, true)) {
                    $v->errors()->add("fields.$i.label", 'The labels "Email" and "Mobile Number" are reserved. Every form already has these two fields.');
                }

                if ($name !== '' && isset($seen[$name])) {
                    $v->errors()->add("fields.$i.label", 'Two fields cannot have the same label: ' . $r['label']);
                }
                $seen[$name] = true;

                if (in_array($r['type'], self::OPTION_TYPES, true) && $this->parseOptions($r['options']) === null) {
                    $v->errors()->add("fields.$i.options", 'Enter at least one option for "' . $r['label'] . '".');
                }

                // Rules for a field that customers have already answered
                $field = $r['key'] !== '' ? $active->get($r['key']) : null;

                if ($form && $field && ! $field->is_system) {
                    $count = $this->answeredCount($form->id, $field->key);

                    if ($count > 0) {
                        $labelChanged = $r['label'] !== $field->label;
                        $typeChanged = $r['type'] !== $field->type;

                        if (($labelChanged || $typeChanged) && $r['label_change'] === null) {
                            $v->errors()->add("fields.$i.label", $count . ' customer(s) have answered "' . $field->label . '". Choose: same question (wording fix) or new question.');
                        }

                        if ($typeChanged && $r['label_change'] !== 'new') {
                            $v->errors()->add("fields.$i.type", 'The type can only change as a new question, because old answers would lose their meaning.');
                        }
                    }
                }
            }
        });

        $validator->validate();

        return $rows;
    }

    /**
     * Save the fields. Existing fields keep their key (and their id).
     * Fields with answers are archived, never deleted.
     * Returns a list of readable changes for the audit log.
     */
    private function syncFields(Form $form, array $rows): array
    {
        $this->ensureSystemFields($form);

        $existing = FormField::where('form_id', $form->id)
            ->whereNull('archived_at')
            ->get()
            ->keyBy('key');

        $keptIds = [];
        $changes = [];

        foreach ($rows as $index => $row) {
            if (in_array($row['key'], self::SYSTEM_KEYS, true)) {
                // System field: only its position can change
                $field = $existing->get($row['key']);

                if ($field) {
                    $field->update(['order' => $index]);
                    $keptIds[] = $field->id;
                }

                continue;
            }

            $data = [
                'label' => $row['label'],
                'type' => $row['type'],
                'options' => in_array($row['type'], self::OPTION_TYPES, true)
                    ? $this->parseOptions($row['options'])
                    : null,
                'is_required' => $row['is_required'],
                'is_readonly' => $row['is_readonly'],
                'order' => $index,
            ];

            $field = $row['key'] !== '' ? $existing->get($row['key']) : null;

            if ($field && ! $field->is_system) {
                $isNewQuestion = $row['label_change'] === 'new'
                    && $this->answeredCount($form->id, $field->key) > 0;

                if ($isNewQuestion) {
                    // Old field is hidden from the form but its data stays
                    $field->update(['archived_at' => now()]);
                    $changes[] = 'archived "' . $field->label . '" (replaced by a new question)';

                    $field = FormField::create($data + [
                        'form_id' => $form->id,
                        'key' => $this->newKey($form),
                        'is_system' => false,
                    ]);
                    $changes[] = 'added "' . $row['label'] . '"';
                } else {
                    if ($field->label !== $row['label']) {
                        $changes[] = 'renamed "' . $field->label . '" to "' . $row['label'] . '"';
                    }
                    if ($field->type !== $row['type']) {
                        $changes[] = 'changed type of "' . $row['label'] . '" from ' . $field->type . ' to ' . $row['type'];
                    }

                    $field->update($data);
                }
            } else {
                $field = FormField::create($data + [
                    'form_id' => $form->id,
                    'key' => $this->newKey($form),
                    'is_system' => false,
                ]);
                $changes[] = 'added "' . $row['label'] . '"';
            }

            $keptIds[] = $field->id;
        }

        // Fields that were removed on the page
        $removed = FormField::where('form_id', $form->id)
            ->where('is_system', false)
            ->whereNull('archived_at')
            ->whereNotIn('id', $keptIds)
            ->get();

        foreach ($removed as $field) {
            if ($this->answeredCount($form->id, $field->key) > 0) {
                $field->update(['archived_at' => now()]);
                $changes[] = 'removed (archived) "' . $field->label . '"';
            } else {
                $field->delete();
                $changes[] = 'removed "' . $field->label . '"';
            }
        }

        $this->renumber($form);

        return $changes;
    }

    /**
     * Every form must have Email and Mobile. Missing ones are added at the top.
     */
    private function ensureSystemFields(Form $form): void
    {
        $have = FormField::where('form_id', $form->id)
            ->where('is_system', true)
            ->pluck('key')
            ->all();

        $defs = [
            FormField::KEY_EMAIL => ['label' => 'Email', 'type' => 'email', 'order' => -2],
            FormField::KEY_MOBILE => ['label' => 'Mobile Number', 'type' => 'text', 'order' => -1],
        ];

        foreach ($defs as $key => $def) {
            if (in_array($key, $have, true)) {
                continue;
            }

            FormField::create([
                'form_id' => $form->id,
                'key' => $key,
                'is_system' => true,
                'label' => $def['label'],
                'type' => $def['type'],
                'options' => null,
                'is_required' => true,
                'is_readonly' => false,
                'order' => $def['order'],
            ]);
        }
    }

    /**
     * Make the order of active fields 0, 1, 2, ... without gaps.
     */
    private function renumber(Form $form): void
    {
        FormField::where('form_id', $form->id)
            ->whereNull('archived_at')
            ->orderBy('order')
            ->orderBy('id')
            ->get()
            ->each(function ($field, $i) {
                if ((int) $field->order !== $i) {
                    $field->update(['order' => $i]);
                }
            });
    }

    /**
     * How many submissions have a real answer for this field key.
     * Keys are generated by us (letters, digits, underscore), so the JSON path is safe.
     */
    private function answeredCount(int $formId, string $key): int
    {
        return FormSubmission::where('form_id', $formId)
            ->whereRaw("JSON_TYPE(JSON_EXTRACT(data, ?)) <> 'NULL'", ['$."' . $key . '"'])
            ->count();
    }

    /**
     * Fingerprint of the active fields. If it changes, the form version goes up.
     */
    private function snapshot(Form $form): string
    {
        return md5(json_encode(
            FormField::where('form_id', $form->id)
                ->whereNull('archived_at')
                ->orderBy('order')
                ->orderBy('id')
                ->get(['key', 'label', 'type', 'options', 'is_required', 'is_readonly'])
                ->toArray()
        ));
    }

    private function newKey(Form $form): string
    {
        do {
            $key = 'f_' . Str::lower(Str::random(8));
        } while (FormField::where('form_id', $form->id)->where('key', $key)->exists());

        return $key;
    }

    /**
     * "A, B, C" becomes ['A', 'B', 'C']. Returns null when nothing is left.
     */
    private function parseOptions(?string $text): ?array
    {
        $list = array_values(array_unique(array_filter(
            array_map('trim', explode(',', (string) $text)),
            fn ($o) => $o !== ''
        )));

        return count($list) ? $list : null;
    }
}