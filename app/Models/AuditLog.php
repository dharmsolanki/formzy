<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'submission_id',
        'meta',
        'ip_address',
        'created_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'created_at' => 'datetime',
    ];

    public static function record(string $action, ?int $submissionId = null, array $meta = []): void
    {
        static::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'submission_id' => $submissionId,
            'meta' => $meta ?: null,
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}