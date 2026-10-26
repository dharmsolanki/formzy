<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FormSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'form_id',
        'data',
        'field_labels',
        'customer_email',
        'customer_mobile',
        'amount',
        'payment_status',
        'razorpay_order_id',
        'razorpay_payment_id',
    ];

    protected $casts = [
        'data' => 'array',
        'field_labels' => 'array',
        'amount' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($submission) {
            if (empty($submission->uuid)) {
                $submission->uuid = (string) Str::uuid();
            }
        });
    }

    public function form()
    {
        return $this->belongsTo(Form::class);
    }

    /**
     * Answer for one field. New records are stored by key, old ones by label.
     */
    public function answer(FormField $field): string
    {
        $data = $this->data ?? [];
        $value = $data[$field->key] ?? $data[$field->label] ?? '';

        return is_array($value) ? implode(', ', $value) : (string) $value;
    }

    /**
     * Raw answer found by label. Temporary, used only by the old CSV export.
     */
    public function answerByLabel(string $label)
    {
        $data = $this->data ?? [];

        foreach (($this->field_labels ?? []) as $key => $savedLabel) {
            if ($savedLabel === $label && array_key_exists($key, $data)) {
                return $data[$key];
            }
        }

        return $data[$label] ?? '';
    }

    /**
     * [label => answer] exactly as the customer saw it when answering.
     */
    public function answersForDisplay(): array
    {
        $labels = $this->field_labels ?? [];
        $out = [];

        foreach (($this->data ?? []) as $key => $value) {
            // Old records are already keyed by label
            $label = $labels[$key] ?? $key;
            $out[$label] = is_array($value) ? implode(', ', $value) : (string) $value;
        }

        return $out;
    }
}