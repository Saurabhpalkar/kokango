<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class SettingController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json(['data' => $this->payload()]);
    }

    public function update(UpdateSettingsRequest $request): JsonResponse
    {
        foreach ($request->validated() as $key => $value) {
            if (array_key_exists($key, Setting::SCHEMA)) {
                Setting::set($key, $value);
            }
        }

        return response()->json(['data' => $this->payload()]);
    }

    private function payload(): array
    {
        return Setting::typed(array_keys(Setting::SCHEMA));
    }
}
