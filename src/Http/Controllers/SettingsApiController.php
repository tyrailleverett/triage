<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Http\Controllers;

use HotReloadStudios\Triage\Models\AgentPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SettingsApiController
{
    public function notifications(Request $request): JsonResponse
    {
        $userId = (string) $request->user()?->getAuthIdentifier();

        $preferences = AgentPreference::firstOrCreate(
            ['user_id' => $userId],
            [
                'notify_ticket_assigned' => true,
                'notify_ticket_replied' => true,
                'notify_note_added' => false,
                'notify_status_changed' => true,
                'daily_digest' => false,
                'email_notifications' => true,
            ],
        );

        return response()->json(['data' => $this->preferencesToArray($preferences)]);
    }

    public function updateNotifications(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'notify_ticket_assigned' => ['required', 'boolean'],
            'notify_ticket_replied' => ['required', 'boolean'],
            'notify_note_added' => ['required', 'boolean'],
            'notify_status_changed' => ['required', 'boolean'],
            'daily_digest' => ['required', 'boolean'],
            'email_notifications' => ['required', 'boolean'],
        ]);

        $userId = (string) $request->user()?->getAuthIdentifier();

        $preferences = AgentPreference::updateOrCreate(
            ['user_id' => $userId],
            $validated,
        );

        return response()->json(['data' => $this->preferencesToArray($preferences)]);
    }

    /**
     * @return array{
     *     notify_ticket_assigned: bool,
     *     notify_ticket_replied: bool,
     *     notify_note_added: bool,
     *     notify_status_changed: bool,
     *     daily_digest: bool,
     *     email_notifications: bool,
     * }
     */
    private function preferencesToArray(AgentPreference $preferences): array
    {
        return [
            'notify_ticket_assigned' => $preferences->notify_ticket_assigned,
            'notify_ticket_replied' => $preferences->notify_ticket_replied,
            'notify_note_added' => $preferences->notify_note_added,
            'notify_status_changed' => $preferences->notify_status_changed,
            'daily_digest' => $preferences->daily_digest,
            'email_notifications' => $preferences->email_notifications,
        ];
    }
}
