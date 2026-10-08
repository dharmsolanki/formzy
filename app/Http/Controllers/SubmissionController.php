<?php

namespace App\Http\Controllers;

use App\Models\Form;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubmissionController extends Controller
{
    public function index(Form $form)
    {
        $this->authorizeForm($form);

        $form->load('fields');

        $submissions = $form->submissions()->latest()->paginate(20);

        return view('submissions.index', compact('form', 'submissions'));
    }

    public function export(Form $form): StreamedResponse
    {
        $this->authorizeForm($form);

        $form->load('fields');

        $labels = $form->fields->pluck('label')->all();
        $filename = 'submissions-' . $form->uuid . '.csv';

        return response()->streamDownload(function () use ($form, $labels) {
            $out = fopen('php://output', 'w');

            fputcsv($out, array_merge(['Date', 'Amount', 'Status', 'Payment ID'], $labels));

            $form->submissions()->latest()->chunk(200, function ($rows) use ($out, $labels) {
                foreach ($rows as $s) {
                    $line = [
                        $s->created_at->format('Y-m-d H:i:s'),
                        $s->amount,
                        $s->payment_status,
                        $s->razorpay_payment_id,
                    ];

                    foreach ($labels as $label) {
                        $value = $s->data[$label] ?? '';
                        $value = is_array($value) ? implode(', ', $value) : (string) $value;
                        $line[] = $this->csvSafe($value);
                    }

                    fputcsv($out, $line);
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * Admin can see any form. Merchant only their own.
     */
    private function authorizeForm(Form $form): void
    {
        $user = auth()->user();

        if ($user->hasRole('admin')) {
            return;
        }

        abort_unless($form->user_id === $user->id, 404);
    }

    /**
     * Prevent CSV formula injection (cells starting with = + - @).
     */
    private function csvSafe(string $value): string
    {
        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@'], true)) {
            return "'" . $value;
        }

        return $value;
    }
}