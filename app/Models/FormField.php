<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormField extends Model
{
    use HasFactory;

    // Keys of the two compulsory fields that every form has
    public const KEY_EMAIL = 'email';
    public const KEY_MOBILE = 'mobile';

    protected $fillable = [
        'form_id',
        'key',
        'is_system',
        'label',
        'type',
        'options',
        'is_required',
        'is_readonly',
        'order',
        'archived_at',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'is_required' => 'boolean',
        'is_readonly' => 'boolean',
        'options' => 'array',
        'archived_at' => 'datetime',
    ];

    public function form()
    {
        return $this->belongsTo(Form::class);
    }
}