<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\FormSubmission;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = auth()->id();

        $forms = Form::where('user_id', $userId)
            ->where('is_complete', true)
            ->withCount([
                'submissions as paid_count' => fn ($q) => $q->where('payment_status', 'paid'),
                'submissions as pending_count' => fn ($q) => $q->where('payment_status', 'pending'),
            ])
            ->withSum([
                'submissions as paid_total' => fn ($q) => $q->where('payment_status', 'paid'),
            ], 'amount')
            ->latest()
            ->get();

        $stats = [
            'forms'         => $forms->count(),
            'paid_count'    => $forms->sum('paid_count'),
            'pending_count' => $forms->sum('pending_count'),
            'paid_total'    => $forms->sum('paid_total'),
        ];

        $recent = FormSubmission::with('form')
            ->whereHas('form', fn ($q) => $q->where('user_id', $userId))
            ->latest()
            ->take(10)
            ->get();

        return view('merchant.dashboard', compact('forms', 'stats', 'recent'));
    }
}