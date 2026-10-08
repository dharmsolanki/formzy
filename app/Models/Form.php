<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Form extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'title',
        'description',
        'amount_type',
        'fixed_amount',
        'is_active',
        'expires_at',
        'wizard_step',
        'is_complete',
        'success_message',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
        'is_complete' => 'boolean',
        'fixed_amount' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($form) {
            if (empty($form->uuid)) {
                $form->uuid = (string) Str::uuid();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function fields()
    {
        return $this->hasMany(FormField::class)->orderBy('order');
    }

    public function paymentConfig()
    {
        return $this->hasOne(FormPaymentConfig::class);
    }

    public function submissions()
    {
        return $this->hasMany(FormSubmission::class);
    }
}
