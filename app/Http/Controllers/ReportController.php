<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        [$isAdmin, $filters, $merchants, $allForms, $forms, $query] = $this->resolve($request);

        $selectedForm = ! empty($filters['form_id'])
            ? Form::with('fields')->find($filters['form_id'])
            : null;

        $summary = (clone $query)->selectRaw("
            COUNT(*) AS total,
            COALESCE(SUM(payment_status = 'paid'), 0) AS paid_count,
            COALESCE(SUM(payment_status = 'pending'), 0) AS pending_count,
            COALESCE(SUM(payment_status = 'failed'), 0) AS failed_count,
            COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN amount END), 0) AS paid_total
        ")->first();

        $byForm = (clone $query)->where('payment_status', 'paid')
            ->select('form_id', DB::raw('COUNT(*) AS cnt'), DB::raw('SUM(amount) AS total'))
            ->groupBy('form_id')
            ->get()
            ->map(function ($row) use ($forms) {
                $form = $forms->firstWhere('id', $row->form_id);
                $row->title = optional($form)->title;
                $row->company = optional(optional($form)->user)->company_name;
                return $row;
            });

        $byDay = (clone $query)->where('payment_status', 'paid')
            ->select(DB::raw('DATE(created_at) AS day'), DB::raw('COUNT(*) AS cnt'), DB::raw('SUM(amount) AS total'))
            ->groupBy('day')
            ->orderByDesc('day')
            ->limit(31)
            ->get();

        $submissions = (clone $query)->with('form.user')->latest()->paginate(20)->withQueryString();

        return view('reports.index', compact(
            'isAdmin', 'filters', 'merchants', 'allForms', 'selectedForm',
            'summary', 'byForm', 'byDay', 'submissions'
        ));
    }

    public function export(Request $request): StreamedResponse
    {
        [$isAdmin, $filters, , , , $query] = $this->resolve($request);

        $selectedForm = ! empty($filters['form_id'])
            ? Form::with('fields')->find($filters['form_id'])
            : null;

        $labels = $selectedForm ? $selectedForm->fields->pluck('label')->all() : [];

        $head = ['Date'];
        if ($isAdmin) {
            $head[] = 'Merchant';
        }
        $head = array_merge($head, ['Form', 'Amount', 'Status', 'Payment ID'], $labels);

        $filename = 'report-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($query, $head, $labels, $isAdmin) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel

            fputcsv($out, $head);

            $query->with('form.user')->chunkByIdDesc(200, function ($rows) use ($out, $labels, $isAdmin) {
                foreach ($rows as $s) {
                    $line = [$s->created_at->format('Y-m-d H:i:s')];

                    if ($isAdmin) {
                        $line[] = $this->cell($s->form->user->company_name);
                    }

                    $line[] = $this->cell($s->form->title);
                    $line[] = $s->amount;
                    $line[] = $s->payment_status;
                    $line[] = $this->cell($s->razorpay_payment_id);

                    foreach ($labels as $label) {
                        $value = $s->data[$label] ?? '';
                        $value = is_array($value) ? implode(', ', $value) : (string) $value;
                        $line[] = $this->cell($value);
                    }

                    fputcsv($out, $line);
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * Validate filters and build the allowed submissions query.
     * Admin sees everything, merchant only their own forms.
     */
    private function resolve(Request $request): array
    {
        $isAdmin = auth()->user()->hasRole('admin');

        $filters = $request->validate([
            'merchant_id' => ['nullable', 'integer'],
            'form_id'     => ['nullable', 'integer'],
            'status'      => ['nullable', 'in:paid,pending,failed'],
            'from'        => ['nullable', 'date'],
            'to'          => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        if (! $isAdmin) {
            $filters['merchant_id'] = null;
        }

        // Admin: form filter works only after a merchant is chosen
        if ($isAdmin && empty($filters['merchant_id'])) {
            $filters['form_id'] = null;
        }

        $merchants = $isAdmin
            ? User::role('merchant')->orderBy('company_name')->get(['id', 'company_name'])
            : collect();

        if (! empty($filters['merchant_id'])) {
            abort_unless($merchants->contains('id', (int) $filters['merchant_id']), 404);
        }

        $allForms = $this->scopedForms()
            ->with('user:id,company_name')
            ->orderBy('title')
            ->get(['id', 'title', 'user_id']);

        $forms = empty($filters['merchant_id'])
            ? $allForms
            : $allForms->where('user_id', (int) $filters['merchant_id'])->values();

        if (! empty($filters['form_id'])) {
            abort_unless($forms->contains('id', (int) $filters['form_id']), 404);
        }

        $query = FormSubmission::query()->whereIn('form_id', $forms->pluck('id'));

        if (! empty($filters['form_id'])) {
            $query->where('form_id', $filters['form_id']);
        }
        if (! empty($filters['status'])) {
            $query->where('payment_status', $filters['status']);
        }
        if (! empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        return [$isAdmin, $filters, $merchants, $allForms, $forms, $query];
    }

    private function scopedForms()
    {
        $query = Form::query();

        if (! auth()->user()->hasRole('admin')) {
            $query->where('user_id', auth()->id());
        }

        return $query;
    }

    /**
     * Stop CSV formula injection (cells starting with = + - @).
     */
    private function cell(?string $value): string
    {
        $value = (string) $value;

        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@'], true)) {
            return "'" . $value;
        }

        return $value;
    }
}