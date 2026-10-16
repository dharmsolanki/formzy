<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Form;
use Illuminate\Http\Request;

class FormController extends Controller
{
    /**
     * Read-only list of the logged-in merchant's own forms.
     */
    public function index(Request $request)
    {
        $filters = $request->validate([
            'form_id' => ['nullable', 'integer'],
            'q'       => ['nullable', 'string', 'max:100'],
            'status'  => ['nullable', 'in:active,inactive'],
        ]);

        // Dropdown options: only this merchant's own complete forms
        $myForms = Form::where('user_id', auth()->id())
            ->where('is_complete', true)
            ->orderBy('title')
            ->get(['id', 'title']);

        // Tampered form id (someone else's form) is rejected
        if (! empty($filters['form_id'])) {
            abort_unless($myForms->contains('id', (int) $filters['form_id']), 404);
        }

        $query = Form::where('user_id', auth()->id())
            ->where('is_complete', true)
            ->withCount([
                'submissions as paid_count' => fn ($q) => $q->where('payment_status', 'paid'),
                'submissions as pending_count' => fn ($q) => $q->where('payment_status', 'pending'),
            ])
            ->withSum([
                'submissions as paid_total' => fn ($q) => $q->where('payment_status', 'paid'),
            ], 'amount');

        if (! empty($filters['form_id'])) {
            $query->where('id', $filters['form_id']);
        }

        if (! empty($filters['q'])) {
            $term = '%' . addcslashes($filters['q'], '%_\\') . '%';
            $query->where('title', 'like', $term);
        }

        if (($filters['status'] ?? '') === 'active') {
            $query->where('is_active', true);
        } elseif (($filters['status'] ?? '') === 'inactive') {
            $query->where('is_active', false);
        }

        $forms = $query->latest()->paginate(15)->withQueryString();

        return view('merchant.forms.index', compact('forms', 'filters', 'myForms'));
    }
}