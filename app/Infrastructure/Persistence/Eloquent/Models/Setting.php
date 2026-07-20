<?php

namespace Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
    ];

    public static function getValue(string $group, string $key, mixed $default = null): mixed
    {
        $cacheKey = static::cacheKey($group, $key);

        $resolved = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($group, $key) {
            $setting = static::query()
                ->where('group', $group)
                ->where('key', $key)
                ->first();

            if (! $setting) {
                return ['missing' => true];
            }

            return [
                'missing' => false,
                'value' => match ($setting->type) {
                    'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
                    'integer' => (int) $setting->value,
                    'json' => json_decode($setting->value, true),
                    'encrypted' => static::decryptValue($setting->value),
                    default => $setting->value,
                },
            ];
        });

        if ($resolved['missing'] ?? true) {
            return $default;
        }

        return $resolved['value'];
    }

    public static function setValue(string $group, string $key, mixed $value, string $type = 'string'): void
    {
        $storedValue = match ($type) {
            'json' => json_encode($value),
            'boolean' => $value ? '1' : '0',
            'encrypted' => Crypt::encryptString((string) $value),
            default => (string) $value,
        };

        static::query()->updateOrCreate(
            ['group' => $group, 'key' => $key],
            ['value' => $storedValue, 'type' => $type]
        );

        Cache::forget(static::cacheKey($group, $key));
        Cache::forget('settings.group.'.$group);
        if ($group === 'features') {
            Cache::forget('settings.features.all');
        }
    }

    public static function forgetGroup(string $group): void
    {
        Cache::forget('settings.group.'.$group);
    }

    private static function cacheKey(string $group, string $key): string
    {
        return "settings.value.{$group}.{$key}";
    }

    private static function decryptValue(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable) {
            return $value;
        }
    }
}
