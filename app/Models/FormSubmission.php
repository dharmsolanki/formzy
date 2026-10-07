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
        'amount',
        'payment_status',
        'razorpay_order_id',
        'razorpay_payment_id',
    ];

    protected $casts = [
        'data' => 'array',
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
}