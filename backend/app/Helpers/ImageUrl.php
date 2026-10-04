<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;

class ImageUrl
{
    /**
     * Absolute URLs and "/"-paths (served by the frontend) are returned as is;
     * anything else is treated as a path on the public storage disk.
     */
    public static function resolve(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $path) || str_starts_with($path, '/')) {
            return $path;
        }

        return Storage::disk('public')->url(ltrim($path, '/'));
    }
}
