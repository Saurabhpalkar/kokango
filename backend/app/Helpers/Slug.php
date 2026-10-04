<?php

namespace App\Helpers;

use Illuminate\Support\Str;

class Slug
{
    /**
     * Builds a slug from $name that is unique in $modelClass's table ("-2", "-3" ... suffixes).
     *
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $modelClass
     */
    public static function unique(string $name, string $modelClass, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'item';
        $slug = $base;
        $i = 2;

        while ($modelClass::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
