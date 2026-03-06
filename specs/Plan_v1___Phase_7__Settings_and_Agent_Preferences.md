# Plan v1 — Phase 7: Settings & Agent Notification Preferences

I have created the following plan after thorough exploration and analysis of the codebase. Follow the below plan verbatim. Trust the files and references. Do not re-verify what's written in the plan. Explore only when absolutely necessary. First implement all the proposed file changes and then I'll review all the changes together at the end.

---

## Design Reference

> **Screenshot**: `art/settings.png`

The Settings page has a fixed "Save Changes" button in the top-right header. A left sub-navigation panel (Profile, Notifications, Appearance, Security) sits inside the main content area. The active sub-nav item is highlighted. For MVP, only the **Notifications** sub-page is fully implemented with real data; the other three sub-pages render placeholder content.

The Notifications sub-page shows a card titled "Notification Preferences" containing 6 toggle rows. Each row has a label, a short description beneath it, and a toggle switch on the right (blue = on, dark = off).

---

## Observations

Phases 1–6 built the complete package foundation, data layer, SDK, email integration, HTTP layer, and ticket management frontend. No per-agent preference storage exists yet. The `triage_agent_preferences` table and its associated model, controller, routes, and frontend pages are entirely new work introduced in this phase. Phase 5's `SettingsController` stub (if present) should be replaced or extended here; otherwise a new controller is created. The host User model key is stored as a string for compatibility, consistent with the existing approach for `submitter_id`, `assignee_id`, and `author_id`.

---

## Approach

This phase adds a Settings area to the Triage dashboard, accessible at `/triage/settings`. It covers three layers:

1. **Data layer** — A new `triage_agent_preferences` table storing per-agent notification toggle states.
2. **HTTP layer** — New Inertia controllers and routes for the settings area.
3. **Frontend** — New React settings pages under `resources/js/Pages/Settings/` with a shared settings sub-navigation layout.

---

## - [ ] 1. Migration

**`database/migrations/create_triage_agent_preferences_table.php.stub`**

Create a new stub migration following the existing pattern in `database/migrations/`.

Schema:

```
triage_agent_preferences
├── id (uuid, primary)
├── user_id (string, not null, unique, indexed)   -- host app user key, stored as string
├── notify_ticket_assigned (boolean, default true)
├── notify_ticket_replied (boolean, default true)
├── notify_note_added (boolean, default false)
├── notify_status_changed (boolean, default true)
├── daily_digest (boolean, default false)
├── email_notifications (boolean, default true)
├── created_at
└── updated_at
```

The `user_id` column has a unique constraint — one row per agent, created on first access (upsert pattern).

Register the migration stub in `TriageServiceProvider::boot()` using `$this->loadMigrationsFrom()` alongside the existing migrations, following the established pattern.

---

## - [ ] 2. Model

**`src/Models/AgentPreference.php`**

```php
<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class AgentPreference extends Model
{
    use HasUuids;

    protected $table = 'triage_agent_preferences';

    protected $fillable = [
        'user_id',
        'notify_ticket_assigned',
        'notify_ticket_replied',
        'notify_note_added',
        'notify_status_changed',
        'daily_digest',
        'email_notifications',
    ];

    protected $casts = [
        'notify_ticket_assigned' => 'boolean',
        'notify_ticket_replied'  => 'boolean',
        'notify_note_added'      => 'boolean',
        'notify_status_changed'  => 'boolean',
        'daily_digest'           => 'boolean',
        'email_notifications'    => 'boolean',
    ];
}
```

---

## - [ ] 3. TypeScript Type Definitions

Add the following interface to **`resources/js/types/index.ts`**:

```ts
interface AgentPreferences {
    notify_ticket_assigned: boolean
    notify_ticket_replied: boolean
    notify_note_added: boolean
    notify_status_changed: boolean
    daily_digest: boolean
    email_notifications: boolean
}
```

---

## - [ ] 4. Settings Controller

**`src/Http/Controllers/SettingsController.php`**

If a `SettingsController` stub exists from Phase 5, replace its body. Otherwise create it fresh.

```php
<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Http\Controllers;

use HotReloadStudios\Triage\Models\AgentPreference;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class SettingsController
{
    public function notifications(Request $request): Response
    {
        $userId = (string) $request->user()->getKey();

        $preferences = AgentPreference::firstOrCreate(
            ['user_id' => $userId],
            [
                'notify_ticket_assigned' => true,
                'notify_ticket_replied'  => true,
                'notify_note_added'      => false,
                'notify_status_changed'  => true,
                'daily_digest'           => false,
                'email_notifications'    => true,
            ]
        );

        return Inertia::render('Settings/Notifications', [
            'preferences' => [
                'notify_ticket_assigned' => $preferences->notify_ticket_assigned,
                'notify_ticket_replied'  => $preferences->notify_ticket_replied,
                'notify_note_added'      => $preferences->notify_note_added,
                'notify_status_changed'  => $preferences->notify_status_changed,
                'daily_digest'           => $preferences->daily_digest,
                'email_notifications'    => $preferences->email_notifications,
            ],
        ]);
    }

    public function updateNotifications(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'notify_ticket_assigned' => ['required', 'boolean'],
            'notify_ticket_replied'  => ['required', 'boolean'],
            'notify_note_added'      => ['required', 'boolean'],
            'notify_status_changed'  => ['required', 'boolean'],
            'daily_digest'           => ['required', 'boolean'],
            'email_notifications'    => ['required', 'boolean'],
        ]);

        $userId = (string) $request->user()->getKey();

        AgentPreference::updateOrCreate(
            ['user_id' => $userId],
            $validated
        );

        return redirect()->route('triage.settings.notifications');
    }
}
```

---

## - [ ] 5. Routes

**Update the routes file** (the file registered in `TriageServiceProvider`, e.g. `routes/triage.php`).

Add the following routes inside the existing authenticated route group, alongside the ticket routes:

```php
// Settings
Route::get('settings', fn () => redirect()->route('triage.settings.notifications'))
    ->name('triage.settings');

Route::get('settings/notifications', [SettingsController::class, 'notifications'])
    ->name('triage.settings.notifications');

Route::patch('settings/notifications', [SettingsController::class, 'updateNotifications'])
    ->name('triage.settings.notifications.update');
```

Import `SettingsController` at the top of the routes file.

The `GET /triage/settings` bare route redirects to `/triage/settings/notifications` so the sidebar nav link for "Settings" lands on a real page.

---

## - [ ] 6. Settings Sub-Navigation Component

**`resources/js/Components/SettingsNav.tsx`**

A vertical sub-navigation list rendered inside the Settings pages. It links to each settings section.

**Props:** `{ active: 'profile' | 'notifications' | 'appearance' | 'security' }`

Renders a list of nav items:

| Label | Route name | Icon suggestion |
|---|---|---|
| Profile | `triage.settings.profile` (placeholder — no href yet) | User circle icon |
| Notifications | `triage.settings.notifications` | Bell icon |
| Appearance | `triage.settings.appearance` (placeholder) | Paint brush icon |
| Security | `triage.settings.security` (placeholder) | Shield icon |

Active item is visually highlighted (slightly lighter background, matching the style in `art/settings.png`). Placeholder items render as non-linking `<span>` elements for MVP since their pages are not implemented yet.

---

## - [ ] 7. Notification Preferences Page

**`resources/js/Pages/Settings/Notifications.tsx`**

> **Design reference**: `art/settings.png`

**Inertia Props:**

| Prop | Type | Source |
|---|---|---|
| `preferences` | `AgentPreferences` | From `SettingsController::notifications()` |

**Page layout** — mirrors `art/settings.png`:

1. **Page header** (full-width top bar within main content):
   - Left: Title "Settings", subtitle "Manage your account and workspace preferences"
   - Right: "Save Changes" button (blue, triggers form submit)

2. **Two-column body**:
   - Left: `SettingsNav` component with `active="notifications"`
   - Right: Main content card with title "Notification Preferences"

3. **Notification toggle rows** — 6 rows in the card, in this order:

   | Toggle key | Label | Description |
   |---|---|---|
   | `notify_ticket_assigned` | New ticket assigned | Get notified when a ticket is assigned to you |
   | `notify_ticket_replied` | Ticket replied | Get notified when a customer replies to your ticket |
   | `notify_note_added` | Internal note added | Get notified when a teammate adds an internal note |
   | `notify_status_changed` | Status changed | Get notified when a ticket status is updated |
   | `daily_digest` | Daily digest | Receive a daily summary of your queue activity |
   | `email_notifications` | Email notifications | Send notifications to your email address |

   Each row:
   - Left: bold label + smaller gray description text below it
   - Right: toggle switch component

4. **Form behavior**:
   - Uses `useForm()` from `@inertiajs/react` initialized with the `preferences` prop values
   - Each toggle's `onChange` calls `setData()` to update the form state
   - The "Save Changes" button submits a `PATCH` request to `route('triage.settings.notifications.update')`
   - On success, the page reloads via Inertia redirect and shows the saved state

**Layout:** Wrapped in `TriageLayout`.

---

## - [ ] 8. Toggle Switch Component

**`resources/js/Components/Toggle.tsx`**

A reusable toggle switch component.

**Props:**
```ts
interface ToggleProps {
    checked: boolean
    onChange: (checked: boolean) => void
    disabled?: boolean
}
```

Renders an accessible toggle switch:
- When `checked` is `true`: blue background (`bg-blue-600`)
- When `checked` is `false`: dark gray background (matching the OFF state in the screenshot)
- Uses a `<button role="switch" aria-checked={checked}>` for accessibility
- The thumb (white circle) slides on toggle
- Clicking calls `onChange(!checked)`

---

## - [ ] 9. Tests

### Feature Tests

**`tests/Feature/Settings/NotificationPreferencesTest.php`**

Test the `SettingsController` HTTP behavior:

```
it saves notification preferences for the authenticated agent
it returns current preferences pre-filled for the authenticated agent
it creates default preferences on first visit if none exist
it returns 403 when the triage gate denies access
it validates that all preference fields must be boolean
it creates separate preferences per user (one agent saving does not affect another)
```

### Unit Tests

**`tests/Unit/Models/AgentPreferenceTest.php`**

```
it uses the correct table name
it casts all preference columns to boolean
it has the expected fillable fields
```

---

## - [ ] 10. Architecture Diagram Update

The Phase 6 frontend architecture diagram should be updated to include the Settings pages. Note this for when you update Phase 6's diagram after implementation:

```
Settings/Notifications → SettingsNav (uses)
Settings/Notifications → Toggle (uses)
app_tsx → Settings/Notifications (resolves)
```
