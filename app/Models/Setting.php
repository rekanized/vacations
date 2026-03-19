<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

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

    public static function encryptedValueFor(string $key, ?string $default = null): ?string
    {
        $value = static::valueFor($key);

        if ($value === null || $value === '') {
            return $default;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable $exception) {
            throw new RuntimeException(sprintf('Unable to decrypt the setting [%s].', $key), previous: $exception);
        }
    }

    public static function writeValue(string $key, ?string $value): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }

    public static function writeEncryptedValue(string $key, ?string $value): void
    {
        static::writeValue(
            $key,
            filled($value) ? Crypt::encryptString($value) : null,
        );
    }
}