<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MerchantCredential extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'razorpay_key_id',
        'razorpay_key_secret',
        'is_verified',
    ];

    protected $casts = [
        'razorpay_key_id' => 'encrypted',
        'razorpay_key_secret' => 'encrypted',
        'is_verified' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}