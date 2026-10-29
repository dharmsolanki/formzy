<?php

namespace App\Services;

use App\Models\Form;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Cell\NumericCell;
use OpenSpout\Common\Entity\Cell\StringCell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use Throwable;

class ReportExcel
{
    /**
     * Build an .xlsx file for one form and return its path.
     * $query must already be limited to this form and to the chosen filters.
     */
    public function build(Form $form, Builder $query, bool $isAdmin): string
    {
        $form->loadMissing('user');

        $active = $form->fields()->get();
        $archived = $form->allFields()->whereNotNull('archived_at')->get();

        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'report-' . Str::uuid() . '.xlsx';

        $writer = new Writer();
        $writer->openToFile($path);

        try {
            $bold = (new Style())->setFontBold();

            /* ---------- Sheet 1: current fields ---------- */

            $writer->getCurrentSheet()->setName('Payments');

            $head = ['Ref No.', 'Date'];
            if ($isAdmin) {
                $head[] = 'Merchant';
            }
            $head = array_merge($head, ['Amount', 'Status'], $active->pluck('label')->all());

            $writer->addRow($this->row($head, $bold));

            $merchant = optional($form->user)->company_name ?? '';

            (clone $query)->chunkByIdDesc(500, function ($rows) use ($writer, $active, $isAdmin, $merchant) {
                foreach ($rows as $s) {
                    $line = [$s->id, $s->created_at->format('Y-m-d H:i:s')];

                    if ($isAdmin) {
                        $line[] = $merchant;
                    }

                    $line[] = (float) $s->amount;
                    $line[] = $s->payment_status;

                    foreach ($active as $field) {
                        $line[] = $s->answer($field);
                    }

                    $writer->addRow($this->row($line));
                }
            });

            /* ---------- Sheet 2: removed fields (only if there is data) ---------- */

            if ($archived->isNotEmpty()) {
                $removedHead = ['Ref No.', 'Date', 'Email', 'Mobile'];
                $removedHead = array_merge($removedHead, $this->removedLabels($archived));

                $created = false;

                (clone $query)->chunkByIdDesc(500, function ($rows) use ($writer, $archived, $removedHead, $bold, &$created) {
                    foreach ($rows as $s) {
                        $values = [];
                        $hasAnswer = false;

                        foreach ($archived as $field) {
                            $answer = $s->answer($field);

                            if ($answer !== '') {
                                $hasAnswer = true;
                            }

                            $values[] = $answer;
                        }

                        if (! $hasAnswer) {
                            continue;
                        }

                        if (! $created) {
                            $writer->addNewSheetAndMakeItCurrent()->setName('Removed fields');
                            $writer->addRow($this->row($removedHead, $bold));
                            $created = true;
                        }

                        $writer->addRow($this->row(array_merge([
                            $s->id,
                            $s->created_at->format('Y-m-d H:i:s'),
                            (string) $s->customer_email,
                            (string) $s->customer_mobile,
                        ], $values)));
                    }
                });
            }
        } catch (Throwable $e) {
            $writer->close();
            @unlink($path);

            throw $e;
        }

        $writer->close();

        return $path;
    }

    /**
     * "Name (removed)". If two removed fields have the same label, a number is added.
     */
    private function removedLabels($archived): array
    {
        $seen = [];
        $labels = [];

        foreach ($archived as $field) {
            $base = $field->label . ' (removed)';
            $seen[$base] = ($seen[$base] ?? 0) + 1;

            $labels[] = $seen[$base] > 1 ? $base . ' ' . $seen[$base] : $base;
        }

        return $labels;
    }

    private function row(array $values, ?Style $style = null): Row
    {
        $cells = array_map(function ($value) {
            if (is_int($value) || is_float($value)) {
                return new NumericCell($value, null);
            }

            return new StringCell($this->safe((string) $value), null);
        }, $values);

        return new Row($cells, $style);
    }

    /**
     * Text that starts with = + - @ is shown with a leading ' so it is never read as a formula.
     */
    private function safe(string $value): string
    {
        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $value;
        }

        return $value;
    }
}