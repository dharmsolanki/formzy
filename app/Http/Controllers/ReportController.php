<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\User;
use App\Services\ReportExcel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

        $byForm = (clone $query)
            ->select(
                'form_id',
                DB::raw('COUNT(*) AS registrations'),
                DB::raw("SUM(payment_status = 'paid') AS paid_count"),
                DB::raw("SUM(payment_status = 'pending') AS pending_count"),
                DB::raw("SUM(CASE WHEN payment_status = 'paid' THEN amount ELSE 0 END) AS total")
            )
            ->groupBy('form_id')
            ->get()
            ->map(function ($row) use ($forms) {
                $form = $forms->firstWhere('id', $row->form_id);
                $row->title = optional($form)->title;
                $row->user_id = optional($form)->user_id;
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

    /**
     * Excel file: Sheet 1 has the current fields, Sheet 2 the removed fields.
     */
    public function export(Request $request, ReportExcel $excel)
    {
        [$isAdmin, $filters, , , , $query] = $this->resolve($request);

        if (empty($filters['form_id'])) {
            return redirect()->route('reports.index', $request->query())
                ->withErrors(['export' => 'Select a form first. The Excel file has one column for each field of that form.']);
        }

        // resolve() has already checked that this form belongs to the allowed set
        $form = Form::with('user')->findOrFail($filters['form_id']);

        @set_time_limit(120);

        $path = $excel->build($form, $query, $isAdmin);

        AuditLog::record('report.export', null, [
            'form_id' => $form->id,
            'filters' => array_keys(array_filter($filters, fn ($v) => $v !== null && $v !== '')),
        ]);

        $filename = (Str::slug($form->title) ?: 'report') . '-' . now()->format('Ymd-His') . '.xlsx';

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
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

        // A merchant with exactly one form sees it already selected
        if (! $isAdmin && empty($filters['form_id']) && $forms->count() === 1) {
            $filters['form_id'] = $forms->first()->id;
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
}