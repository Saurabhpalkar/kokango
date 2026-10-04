<?php

namespace App\Http\Controllers\Concerns;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * For public routes that behave differently when a Sanctum token is present.
 */
trait ResolvesViewer
{
    protected function viewer(): ?User
    {
        /** @var User|null $user */
        $user = Auth::guard('sanctum')->user();

        return $user;
    }
}
