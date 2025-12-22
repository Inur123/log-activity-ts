<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Application extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name', 'slug', 'domain', 'stack', 'is_active','api_key'
        // api_key tidak wajib di-fill manual
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $app) {
            if (!$app->id) {
                $app->id = (string) Str::uuid();
            }

            // auto-generate token (64 chars)
            if (!$app->api_key) {
                $app->api_key = self::generateApiKey();
            }
        });
    }

    public static function generateApiKey(): string
    {
        // 64 char hex
        return bin2hex(random_bytes(32));
    }

    public function logs(): HasMany
    {
        return $this->hasMany(UnifiedLog::class);
    }
}
