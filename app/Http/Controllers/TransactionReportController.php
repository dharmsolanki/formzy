<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Razorpay\Api\Api;
use Throwable;

class TransactionReportController extends Controller
{
    public function index(Request $request)
    {
        $f = $request->validate([
            'ref'         => ['nullable', 'string', 'max:100'],
            'merchant_id' => ['nullable', 'integer'],
            'form_id'     => ['nullable', 'integer'],
            'q'           => ['nullable', 'string', 'min:3', 'max:100'],
            'amount'      => ['nullable', 'numeric', 'min:0'],
            'status'      => ['nullable', 'in:paid,pending,failed'],
            'from'        => ['nullable', 'date'],
            'to'          => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $merchants = User::role('merchant')->orderBy('company_name')->get(['id', 'company_name']);

        $query = FormSubmission::query()->with('form.user');

        if (! empty($f['ref'])) {
            // Exact lookup: other filters are ignored
            $ref = $f['ref'];

            $query->where(function ($q) use ($ref) {
                $q->where('razorpay_payment_id', $ref)
                    ->orWhere('razorpay_order_id', $ref)
                    ->orWhere('uuid', $ref);
            });

            $f = ['ref' => $ref];
        } else {
            // Form filter works only after a merchant is chosen
            if (empty($f['merchant_id'])) {
                $f['form_id'] = null;
            }

            if (! empty($f['merchant_id'])) {
                abort_unless($merchants->contains('id', (int) $f['merchant_id']), 404);
                $query->whereHas('form', fn ($q) => $q->where('user_id', $f['merchant_id']));
            }

            if (! empty($f['form_id'])) {
                abort_unless(
                    Form::where('id', $f['form_id'])->where('user_id', $f['merchant_id'])->exists(),
                    404
                );
                $query->where('form_id', $f['form_id']);
            }

            $narrowed = ! empty($f['merchant_id']) || ! empty($f['form_id'])
                || ! empty($f['from']) || ! empty($f['to']);

            if (! empty($f['q'])) {
                if (! $narrowed) {
                    throw ValidationException::withMessages([
                        'q' => 'Select a merchant, form or date range to search customer data.',
                    ]);
                }

                $term = '%' . addcslashes(mb_strtolower($f['q']), '%_\\') . '%';
                $query->whereRaw('LOWER(CAST(data AS CHAR)) LIKE ?', [$term]);
            }

            if (isset($f['amount']) && $f['amount'] !== null) {
                $query->where('amount', $f['amount']);
            }
            if (! empty($f['status'])) {
                $query->where('payment_status', $f['status']);
            }
            if (! empty($f['from'])) {
                $query->whereDate('created_at', '>=', $f['from']);
            }
            if (! empty($f['to'])) {
                $query->whereDate('created_at', '<=', $f['to']);
            }
        }

        $used = array_keys(array_filter($f, fn ($v) => $v !== null && $v !== ''));

        if ($used) {
            // Filter names only. The customer search text is never stored.
            AuditLog::record('transaction.search', null, [
                'filters' => $used,
                'ref' => $f['ref'] ?? null,
            ]);
        }

        $submissions = $query->latest()->paginate(20)->withQueryString();

        $submissions->getCollection()->transform(function ($s) {
            $s->customer_preview = $this->preview($s->data ?? []);
            return $s;
        });

        return view('reports.transactions', [
            'filters'     => $f,
            'merchants'   => $merchants,
            'submissions' => $submissions,
            'hasFilters'  => (bool) $used,
        ]);
    }

    /**
     * Detail page with full (unmasked) data. Every view is logged.
     */
    public function show(string $uuid)
    {
        $submission = FormSubmission::where('uuid', $uuid)
            ->with('form.user', 'form.fields')
            ->firstOrFail();

        AuditLog::record('transaction.view', $submission->id);

        $history = AuditLog::with('user:id,name')
            ->where('submission_id', $submission->id)
            ->latest('created_at')
            ->limit(10)
            ->get();

        return view('reports.transaction-show', compact('submission', 'history'));
    }

    /**
     * Ask Razorpay (with this form's own key) what really happened. Read-only.
     */
    public function check(string $uuid)
    {
        $submission = FormSubmission::where('uuid', $uuid)->with('form.paymentConfig')->firstOrFail();
        $config = $submission->form->paymentConfig;

        if (! $config) {
            return back()->withErrors(['razorpay' => 'This form has no payment configuration.']);
        }

        if (! $submission->razorpay_order_id && ! $submission->razorpay_payment_id) {
            return back()->withErrors(['razorpay' => 'No Razorpay order or payment exists for this record.']);
        }

        try {
            [$order, $payments] = $this->fetchFromRazorpay($submission);
        } catch (Throwable $e) {
            AuditLog::record('transaction.razorpay_check_failed', $submission->id, [
                'error' => class_basename($e),
            ]);

            return back()->withErrors(['razorpay' => 'Razorpay check failed: ' . $e->getMessage()]);
        }

        $statuses = collect($payments)->pluck('status');
        $captured = $statuses->contains('captured');
        $refunded = $statuses->contains('refunded');

        if ($captured) {
            $state = 'captured';
        } elseif ($refunded) {
            $state = 'refunded';
        } elseif ($statuses->contains('authorized')) {
            $state = 'authorized (not captured)';
        } elseif (count($payments)) {
            $state = 'not paid (' . $statuses->unique()->implode(', ') . ')';
        } else {
            $state = 'no payment attempt';
        }

        $formzyPaid = $submission->payment_status === 'paid';

        if ($refunded && ! $captured) {
            $verdict = 'review';
            $message = 'Razorpay shows a refund. Review manually.';
        } elseif ($formzyPaid === $captured) {
            $verdict = 'match';
            $message = 'Razorpay and Formzy agree.';
        } else {
            $verdict = 'mismatch';
            $message = 'Mismatch: Razorpay says "' . $state . '", Formzy says "' . $submission->payment_status . '".';
        }

        $expectedPaise = (int) round($submission->amount * 100);
        $amountWarning = collect($payments)
            ->where('status', 'captured')
            ->contains(fn ($p) => (int) $p['amount'] !== $expectedPaise);

        $sync = $this->evaluateSync($submission, $payments);

        AuditLog::record('transaction.razorpay_check', $submission->id, [
            'verdict' => $verdict,
            'razorpay_state' => $state,
        ]);

        return redirect()->route('reports.transactions.show', $submission->uuid)->with('rzp', [
            'verdict'         => $verdict,
            'message'         => $message,
            'state'           => $state,
            'mode'            => str_starts_with($config->razorpay_key_id, 'rzp_live_') ? 'Live' : 'Test',
            'order'           => $order,
            'payments'        => $payments,
            'amount_warning'  => $amountWarning,
            'can_sync'        => $sync['ok'],
            'sync_reason'     => $sync['reason'],
            'sync_payment_id' => $sync['payment']['id'] ?? null,
            'checked_at'      => now()->format('d M Y, h:i:s A'),
        ]);
    }

    /**
     * Mark a record as paid, but only with proof from Razorpay (re-checked live).
     */
    public function sync(string $uuid)
    {
        $submission = FormSubmission::where('uuid', $uuid)->with('form.paymentConfig')->firstOrFail();

        if (! $submission->form->paymentConfig) {
            return back()->withErrors(['razorpay' => 'This form has no payment configuration.']);
        }

        try {
            [, $payments] = $this->fetchFromRazorpay($submission);
        } catch (Throwable $e) {
            AuditLog::record('transaction.sync_failed', $submission->id, [
                'error' => class_basename($e),
            ]);

            return back()->withErrors(['razorpay' => 'Razorpay check failed: ' . $e->getMessage()]);
        }

        $eval = $this->evaluateSync($submission, $payments);

        if (! $eval['ok']) {
            AuditLog::record('transaction.sync_refused', $submission->id, [
                'reason' => $eval['reason'],
            ]);

            return redirect()->route('reports.transactions.show', $submission->uuid)
                ->withErrors(['razorpay' => 'Sync refused: ' . $eval['reason']]);
        }

        $payment = $eval['payment'];

        $result = DB::transaction(function () use ($submission, $payment) {
            // Lock the row so two quick clicks cannot update twice
            $fresh = FormSubmission::whereKey($submission->id)->lockForUpdate()->first();

            if ($fresh->payment_status === 'paid') {
                return 'already';
            }

            $before = $fresh->payment_status;

            $fresh->update([
                'payment_status' => 'paid',
                'razorpay_payment_id' => $payment['id'],
            ]);

            AuditLog::record('transaction.synced_from_razorpay', $fresh->id, [
                'before' => $before,
                'after' => 'paid',
                'payment_id' => $payment['id'],
                'amount_paise' => $payment['amount'],
            ]);

            return 'done';
        });

        if ($result === 'already') {
            return redirect()->route('reports.transactions.show', $submission->uuid)
                ->withErrors(['razorpay' => 'This record is already marked as paid.']);
        }

        return redirect()->route('reports.transactions.show', $submission->uuid)
            ->with('success', 'Record marked as paid using Razorpay payment ' . $payment['id'] . '.');
    }

    /**
     * Fetch the order and its payments from Razorpay using this form's own key.
     */
    private function fetchFromRazorpay(FormSubmission $submission): array
    {
        $config = $submission->form->paymentConfig;
        $api = new Api($config->razorpay_key_id, $config->razorpay_key_secret);

        $order = null;
        $payments = [];

        if ($submission->razorpay_order_id) {
            $o = $api->order->fetch($submission->razorpay_order_id);

            $order = [
                'id'          => $o['id'],
                'status'      => $o['status'],
                'amount'      => $o['amount'],
                'amount_paid' => $o['amount_paid'],
                'attempts'    => $o['attempts'],
            ];

            $list = $o->payments();
            foreach ((array) ($list['items'] ?? []) as $p) {
                $payments[] = $this->mapPayment($p);
            }
        }

        if (empty($payments) && $submission->razorpay_payment_id) {
            $payments[] = $this->mapPayment($api->payment->fetch($submission->razorpay_payment_id));
        }

        return [$order, $payments];
    }

    /**
     * Decide whether this record can be safely marked as paid.
     */
    private function evaluateSync(FormSubmission $submission, array $payments): array
    {
        $result = ['ok' => false, 'reason' => null, 'payment' => null];

        $captured = collect($payments)->where('status', 'captured')->values();
        $hasRefund = collect($payments)->contains(
            fn ($p) => $p['status'] === 'refunded' || $p['amount_refunded'] > 0
        );
        $expectedPaise = (int) round($submission->amount * 100);

        if ($submission->payment_status === 'paid') {
            $result['reason'] = 'Formzy already shows this record as paid. Nothing to sync.';
        } elseif (! $submission->razorpay_order_id) {
            $result['reason'] = 'This record has no Razorpay order ID.';
        } elseif ($captured->count() === 0) {
            $result['reason'] = 'Razorpay shows no captured payment on this order.';
        } elseif ($captured->count() > 1) {
            $result['reason'] = 'More than one captured payment on this order. Review manually.';
        } elseif ($hasRefund) {
            $result['reason'] = 'A refund exists on this order. Review manually.';
        } else {
            $p = $captured->first();

            if ($p['order_id'] !== $submission->razorpay_order_id) {
                $result['reason'] = 'The payment belongs to a different order.';
            } elseif ((int) $p['amount'] !== $expectedPaise || $p['currency'] !== 'INR') {
                $result['reason'] = 'Captured amount does not match the amount in Formzy.';
            } else {
                $result['ok'] = true;
                $result['payment'] = $p;
            }
        }

        return $result;
    }

    private function mapPayment($p): array
    {
        return [
            'id'              => $p['id'],
            'status'          => $p['status'],
            'method'          => $p['method'] ?? null,
            'amount'          => $p['amount'],
            'amount_refunded' => (int) ($p['amount_refunded'] ?? 0),
            'currency'        => $p['currency'] ?? 'INR',
            'order_id'        => $p['order_id'] ?? null,
            'created_at'      => Carbon::createFromTimestamp($p['created_at'], config('app.timezone'))->format('d M Y, h:i A'),
        ];
    }

    /**
     * First few answers of the customer, masked.
     */
    private function preview(array $data): string
    {
        $parts = [];

        foreach ($data as $value) {
            $value = is_array($value) ? implode(', ', $value) : (string) $value;
            $masked = $this->mask($value);

            if ($masked !== '') {
                $parts[] = $masked;
            }
            if (count($parts) === 3) {
                break;
            }
        }

        return implode(' · ', $parts);
    }

    private function mask(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
            [$name, $domain] = explode('@', $value, 2);
            return mb_substr($name, 0, 2) . '***@' . $domain;
        }

        $digits = preg_replace('/\D/', '', $value);
        if (strlen($digits) >= 8) {
            return substr($digits, 0, 2) . str_repeat('*', strlen($digits) - 4) . substr($digits, -2);
        }

        return mb_substr($value, 0, 2) . '***';
    }
}