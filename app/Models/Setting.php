<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Setting extends Model
{
    /**
     * @var array<string, string|null>
     */
    protected static array $valueCache = [];

    protected $fillable = [
        'key',
        'value',
    ];

    protected static function booted(): void
    {
        static::saved(function (Setting $setting): void {
            unset(static::$valueCache[$setting->key]);
        });

        static::deleted(function (Setting $setting): void {
            unset(static::$valueCache[$setting->key]);
        });
    }

    public static function valueFor(string $key, ?string $default = null): ?string
    {
        if (! Schema::hasTable('settings')) {
            return $default;
        }

        if (array_key_exists($key, static::$valueCache)) {
            return static::$valueCache[$key] ?? $default;
        }

        static::$valueCache[$key] = static::query()->where('key', $key)->value('value');

        return static::$valueCache[$key] ?? $default;
    }
}