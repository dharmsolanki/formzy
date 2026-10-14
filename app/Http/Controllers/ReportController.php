<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\FormSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'form_id' => ['nullable', 'integer'],
            'status'  => ['nullable', 'in:paid,pending,failed'],
            'from'    => ['nullable', 'date'],
            'to'      => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $forms = $this->scopedForms()->orderBy('title')->get(['id', 'title']);

        // A merchant must not filter by someone else's form
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
                $row->title = optional($forms->firstWhere('id', $row->form_id))->title;
                return $row;
            });

        $byDay = (clone $query)->where('payment_status', 'paid')
            ->select(DB::raw('DATE(created_at) AS day'), DB::raw('COUNT(*) AS cnt'), DB::raw('SUM(amount) AS total'))
            ->groupBy('day')
            ->orderByDesc('day')
            ->limit(31)
            ->get();

        $submissions = (clone $query)->with('form')->latest()->paginate(20)->withQueryString();

        return view('reports.index', compact('filters', 'forms', 'summary', 'byForm', 'byDay', 'submissions'));
    }

    /**
     * Admin sees all forms. Merchant only their own.
     */
    private function scopedForms()
    {
        $query = Form::query();

        if (! auth()->user()->hasRole('admin')) {
            $query->where('user_id', auth()->id());
        }

        return $query;
    }
}