<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'merchants'     => User::role('merchant')->count(),
            'forms_live'    => Form::where('is_complete', true)->where('is_active', true)->count(),
            'forms_draft'   => Form::where('is_complete', false)->count(),
            'paid_count'    => FormSubmission::where('payment_status', 'paid')->count(),
            'pending_count' => FormSubmission::where('payment_status', 'pending')->count(),
            'paid_total'    => FormSubmission::where('payment_status', 'paid')->sum('amount'),
        ];

        $recent = FormSubmission::with('form.user')->latest()->take(10)->get();

        return view('admin.dashboard', compact('stats', 'recent'));
    }
}