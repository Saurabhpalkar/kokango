<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /** Every known key with its default; kind is "int", "num" or "string". */
    public const SCHEMA = [
        'store_name' => ['Kokango', 'string'],
        'store_phone' => ['', 'string'],
        'store_email' => ['', 'string'],
        'store_address' => ['', 'string'],
        'whatsapp_number' => ['', 'string'],
        'shipping_standard_paise' => [6000, 'int'],
        'shipping_express_paise' => [12000, 'int'],
        'free_shipping_above_paise' => [0, 'int'],
        'free_shipping_note' => ['', 'string'],
        'gst_percent' => [5, 'num'],
        'notification_email' => ['', 'string'],
    ];

    /**
     * Returns the stored value (a string, as stored) or $default when the key is missing or null.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $value = static::query()->where('key', $key)->value('value');

        return $value === null ? $default : $value;
    }

    public static function set(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value === null ? null : (is_bool($value) ? ($value ? '1' : '0') : (string) $value)]
        );
    }

    /**
     * @return array<string, string|null>
     */
    public static function allAsArray(): array
    {
        return static::query()->pluck('value', 'key')->all();
    }

    /**
     * Typed values (ints for paise, number for gst_percent, strings otherwise) for the given keys.
     *
     * @param  array<int, string>  $keys
     * @return array<string, int|float|string>
     */
    public static function typed(array $keys): array
    {
        $stored = static::allAsArray();
        $out = [];

        foreach ($keys as $key) {
            [$default, $kind] = self::SCHEMA[$key] ?? [null, 'string'];
            $value = $stored[$key] ?? null;

            if ($value === null || ($kind !== 'string' && ! is_numeric($value))) {
                $out[$key] = $default;
            } elseif ($kind === 'int') {
                $out[$key] = (int) $value;
            } elseif ($kind === 'num') {
                $out[$key] = $value + 0;
            } else {
                $out[$key] = (string) $value;
            }
        }

        return $out;
    }
}
