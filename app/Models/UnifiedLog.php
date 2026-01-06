<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class UnifiedLog extends Model
{
    use HasUuids;

    const UPDATED_AT = null;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'application_id',
        'seq',
        'log_type',
        'payload',
        'hash',
        'prev_hash',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'payload' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function () {
            throw new \RuntimeException('Cannot update immutable log');
        });

        static::deleting(function () {
            throw new \RuntimeException('Cannot delete immutable log');
        });
    }

    public function application()
    {
        return $this->belongsTo(Application::class);
    }
}
