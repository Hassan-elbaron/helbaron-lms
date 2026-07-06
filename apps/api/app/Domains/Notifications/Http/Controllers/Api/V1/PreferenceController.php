<?php

namespace App\Domains\Notifications\Http\Controllers\Api\V1;

use App\Domains\Notifications\Actions\UpdatePreferencesAction;
use App\Domains\Notifications\Http\Requests\UpdatePreferencesRequest;
use App\Shared\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class PreferenceController extends Controller
{
    public function update(UpdatePreferencesRequest $request, UpdatePreferencesAction $action): JsonResponse
    {
        $setting = $action->execute($request->user(), $request->validated());

        return ApiResponse::updated([
            'locale' => $setting->locale,
            'digest_frequency' => $setting->digest_frequency->value,
            'timezone' => $setting->timezone,
        ], 'Preferences updated.');
    }
}
