<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Application extends Model
{
    protected $fillable = [
        'name', 'slug', 'api_key', 'domain', 'stack', 'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function logs(): HasMany
    {
        return $this->hasMany(UnifiedLog::class);
    }
}
