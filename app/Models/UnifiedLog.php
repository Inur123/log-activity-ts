<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class UnifiedLog extends Model
{
    const UPDATED_AT = null; // immutable

    protected $fillable = [
        'application_id',
        'log_type',
        'payload',
        'hash',
        'prev_hash',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'payload'     => 'array',
        'created_at'  => 'datetime',
    ];

    protected static function booted()
    {
        static::updating(function () {
            throw new \Exception('Cannot update immutable log');
        });

        static::deleting(function () {
            throw new \Exception('Cannot delete immutable log');
        });
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function scopeByApplication($query, $appId)
    {
        return $query->where('application_id', $appId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('log_type', $type);
    }

    public function scopeDateRange($query, $start, $end)
    {
        return $query
            ->when($start, fn($q) => $q->where('created_at', '>=', Carbon::parse($start)->startOfDay()))
            ->when($end,   fn($q) => $q->where('created_at', '<=', Carbon::parse($end)->endOfDay()));
    }

    // MySQL-friendly search JSON
    public function scopeSearchInPayload($query, $search)
    {
        $search = trim((string) $search);
        if ($search === '') return $query;

        return $query->whereRaw("CAST(payload AS CHAR) LIKE ?", ["%{$search}%"]);
    }

    public function getPayloadValue($key, $default = null)
    {
        return data_get($this->payload, $key, $default);
    }
    public function getRouteKeyName(): string
{
    return 'id';
}


}
