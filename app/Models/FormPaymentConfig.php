<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormPaymentConfig extends Model
{
    protected $fillable = [
        'form_id',
        'razorpay_key_id',
        'razorpay_key_secret',
        'webhook_secret',
        'is_verified',
    ];

    protected $casts = [
        'razorpay_key_id' => 'encrypted',
        'razorpay_key_secret' => 'encrypted',
        'webhook_secret' => 'encrypted',
        'is_verified' => 'boolean',
    ];

    public function form()
    {
        return $this->belongsTo(Form::class);
    }
}
