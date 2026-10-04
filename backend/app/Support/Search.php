<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Safe handling of free-text "search" query parameters.
 */
class Search
{
    /** The query-string value as a trimmed string ("" when missing or not a plain string, e.g. ?search[]=x). */
    public static function term(Request $request, string $key = 'search', int $max = 100): string
    {
        $value = $request->query($key);

        return is_string($value) ? mb_substr(trim($value), 0, $max) : '';
    }

    /** "%term%" with LIKE wildcards escaped (escape character is "!", see {@see self::where()}). */
    public static function like(string $term): string
    {
        return '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term).'%';
    }

    /**
     * Adds "(col1 LIKE %term% OR col2 LIKE %term% ...)" to the query. Column names must be
     * trusted constants (they are interpolated); the term is always bound.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $query
     * @param  array<int, string>  $columns
     */
    public static function where($query, array $columns, string $term): void
    {
        $like = self::like($term);

        $query->where(function ($q) use ($columns, $like) {
            foreach ($columns as $column) {
                $q->orWhereRaw($column." like ? escape '!'", [$like]);
            }
        });
    }
}
