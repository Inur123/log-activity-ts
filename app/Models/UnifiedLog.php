<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnifiedLog extends Model
{
    const UPDATED_AT = null; // Immutable log

    protected $fillable = [
        'application_id',
        'log_type',
        'payload',
        'hash',
        'prev_hash',
        'ip_address',
        'user_agent'
    ];

    protected $casts = [
        'payload' => 'array',
        'created_at' => 'datetime'
    ];

    // PREVENT UPDATE & DELETE
    public function save(array $options = [])
    {
        if ($this->exists) {
            throw new \Exception('Cannot update immutable log');
        }
        return parent::save($options);
    }

    public function delete()
    {
        throw new \Exception('Cannot delete immutable log');
    }

    // RELATIONSHIPS
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    // SCOPES
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
        return $query->whereBetween('created_at', [$start, $end]);
    }

    public function scopeSearchInPayload($query, $search)
    {
        return $query->where('payload', 'like', '%' . $search . '%');
    }

    // HELPER: Get value from payload
    public function getPayloadValue($key, $default = null)
    {
        return data_get($this->payload, $key, $default);
    }
}
