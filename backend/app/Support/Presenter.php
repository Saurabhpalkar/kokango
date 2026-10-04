<?php

namespace App\Support;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Facades\Storage;

class Presenter
{
    /** Absolute URL, frontend path starting with "/", or a public-disk storage path. */
    public static function imageUrl(?string $image): ?string
    {
        if ($image === null || $image === '') {
            return null;
        }

        if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://') || str_starts_with($image, '/')) {
            return $image;
        }

        return Storage::disk('public')->url($image);
    }

    public static function iso(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value)->toIso8601String();
        }

        return Carbon::parse($value)->toIso8601String();
    }
}
