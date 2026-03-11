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
            'data' => $this->defaultPreferences(),
        ]);
    }

    public function updateNotifications(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'notify_on_new_ticket' => ['sometimes', 'boolean'],
            'notify_on_reply' => ['sometimes', 'boolean'],
            'notify_on_assignment' => ['sometimes', 'boolean'],
            'notify_on_status_change' => ['sometimes', 'boolean'],
        ]);

        return response()->json([
            'data' => array_merge($this->defaultPreferences(), $validated),
        ]);
    }

    /**
     * @return array{notify_on_new_ticket: bool, notify_on_reply: bool, notify_on_assignment: bool, notify_on_status_change: bool}
     */
    private function defaultPreferences(): array
    {
        return [
            'notify_on_new_ticket' => false,
            'notify_on_reply' => false,
            'notify_on_assignment' => false,
            'notify_on_status_change' => false,
        ];
    }
}
