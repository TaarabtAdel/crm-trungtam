<?php

namespace App\Models;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    protected static function cacheKey(): string
    {
        return TenantContext::cacheKey('app_settings');
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $settings = Cache::remember(static::cacheKey(), 60, function () {
            return static::query()->pluck('value', 'key')->all();
        });

        return $settings[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget(static::cacheKey());
    }
}
