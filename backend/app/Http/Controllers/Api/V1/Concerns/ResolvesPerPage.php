<?php

namespace App\Http\Controllers\Api\V1\Concerns;

use Illuminate\Http\Request;

trait ResolvesPerPage
{
    protected function perPage(Request $request, int $default = 20): int
    {
        return max(1, min(100, (int) $request->query('per_page', $default)));
    }
}
