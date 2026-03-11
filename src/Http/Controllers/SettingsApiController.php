<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SettingsApiController
{
    public function notifications(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'agent_id' => $request->user()?->getKey(),
                'preferences' => [],
            ],
        ]);
    }

    public function updateNotifications(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'agent_id' => $request->user()?->getKey(),
                'preferences' => [],
            ],
        ]);
    }
}
