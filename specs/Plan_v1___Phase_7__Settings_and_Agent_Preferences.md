# Plan v1 — Phase 7: Settings & Agent Notification Preferences

I have created the following plan after thorough exploration and analysis of the codebase. Follow the below plan verbatim. Trust the files and references. Do not re-verify what's written in the plan. Explore only when absolutely necessary. First implement all the proposed file changes and then I'll review all the changes together at the end.

---

## Design Reference

> **Screenshot**: `art/settings.png`

The Settings page has a fixed "Save Changes" button in the top-right header. A left sub-navigation panel (Profile, Notifications, Appearance, Security) sits inside the main content area. The active sub-nav item is highlighted. For MVP, only the **Notifications** sub-page is fully implemented with real data; the other three sub-pages render placeholder content.

The Notifications sub-page shows a card titled "Notification Preferences" containing 6 toggle rows. Each row has a label, a short description beneath it, and a toggle switch on the right (blue = on, dark = off).

---

## Observations

Phases 1 through 6 built the package foundation, data layer, SDK, email integration, Blade shell, JSON API, and standalone React dashboard. No per-agent preference storage exists yet. The `triage_agent_preferences` table and its associated model, API controller behavior, and frontend screens are entirely new work introduced in this phase. The host User model key is stored as a string for compatibility, consistent with the existing approach for `submitter_id`, `assignee_id`, and `author_id`.

---

## Approach

This phase adds a Settings area to the Triage dashboard, accessible at `/triage/settings` in the browser and backed by `/triage/api/settings/notifications` on the server.

It covers three layers:

1. **Data layer** — A new `triage_agent_preferences` table storing per-agent notification toggle states.
2. **HTTP layer** — JSON endpoints for fetching and saving the authenticated agent's preferences.
3. **Frontend** — React settings pages under `resources/js/Pages/Settings/` with a shared settings sub-navigation layout.

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

The `user_id` column has a unique constraint — one row per agent, created on first access.

Register the migration stub in `TriageServiceProvider::boot()` using `$this->loadMigrationsFrom()` alongside the existing migrations.

---

## - [ ] 2. Model

**`src/Models/AgentPreference.php`**

Create a `final` Eloquent model with:
- `HasUuids`
- `$table = 'triage_agent_preferences'`
- `$fillable` including all preference columns plus `user_id`
- `$casts` for every toggle column as `boolean`

---

## - [ ] 3. TypeScript Type Definitions

Add `AgentPreferences` to **`resources/js/types/index.ts`**:

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

## - [ ] 4. Settings API Controller

**`src/Http/Controllers/SettingsApiController.php`**

If a stub was created in Phase 5, replace it with the real implementation.

Methods:

### `notifications(Request $request): JsonResponse`

1. Resolve the authenticated user's key as a string.
2. `firstOrCreate()` an `AgentPreference` row for that user with default values.
3. Return `200` JSON with the normalized preference payload.

### `updateNotifications(Request $request): JsonResponse`

1. Validate all preference fields as required booleans.
2. Resolve the authenticated user's key as a string.
3. `updateOrCreate()` the preference row.
4. Return `200` JSON with the saved preference payload.

This controller should not return redirects. It is a pure JSON endpoint consumed by the React dashboard.

---

## - [ ] 5. Routes

Update the Triage routes file so the settings API lives under the existing authorized `/triage/api` group.

Required routes:

```php
Route::get('api/settings/notifications', [SettingsApiController::class, 'notifications'])
    ->name('triage.api.settings.notifications.show');

Route::patch('api/settings/notifications', [SettingsApiController::class, 'updateNotifications'])
    ->name('triage.api.settings.notifications.update');
```

Do not add server-side redirect routes for `/triage/settings`. The dashboard shell catch-all from Phase 5 already serves that URL, and the frontend router handles redirecting `/settings` to `/settings/notifications` client-side.

---

## - [ ] 6. Settings Sub-Navigation Component

**`resources/js/Components/SettingsNav.tsx`**

A vertical sub-navigation list rendered inside the Settings pages.

**Props:** `{ active: 'profile' | 'notifications' | 'appearance' | 'security' }`

Renders nav items:

| Label | Path | Icon suggestion |
|---|---|---|
| Profile | placeholder only | User circle icon |
| Notifications | `/settings/notifications` | Bell icon |
| Appearance | placeholder only | Paint brush icon |
| Security | placeholder only | Shield icon |

Rules:
- Active item is visually highlighted.
- Real destinations use `NavLink` or `Link` from `react-router-dom`.
- Placeholder items render as non-linking `<span>` elements for MVP.

---

## - [ ] 7. Notification Preferences Page

**`resources/js/Pages/Settings/Notifications.tsx`**

The page mirrors `art/settings.png`.

### Data flow

1. Fetch preferences from `GET /triage/api/settings/notifications` on mount.
2. Initialize local component state with the returned `AgentPreferences` payload.
3. Render a loading state until the initial fetch completes.

### Layout

1. **Page header**:
   - Left: Title `Settings`, subtitle `Manage your account and workspace preferences`
   - Right: `Save Changes` button
2. **Two-column body**:
   - Left: `SettingsNav` with `active="notifications"`
   - Right: Main content card titled `Notification Preferences`

### Toggle rows

| Toggle key | Label | Description |
|---|---|---|
| `notify_ticket_assigned` | New ticket assigned | Get notified when a ticket is assigned to you |
| `notify_ticket_replied` | Ticket replied | Get notified when a customer replies to your ticket |
| `notify_note_added` | Internal note added | Get notified when a teammate adds an internal note |
| `notify_status_changed` | Status changed | Get notified when a ticket status is updated |
| `daily_digest` | Daily digest | Receive a daily summary of your queue activity |
| `email_notifications` | Email notifications | Send notifications to your email address |

Each row renders:
- label
- description
- toggle switch on the right

### Save behavior

1. Clicking `Save Changes` sends `PATCH /triage/api/settings/notifications` with the local state payload.
2. While the request is pending, disable the button and toggles.
3. On success, keep the saved state in local memory and show a lightweight success state.
4. On `422`, surface field errors.
5. On network/server failure, show a non-blocking error state and keep unsaved edits visible.

Wrap the page in `TriageLayout`.

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
- `checked = true` → blue background
- `checked = false` → dark gray background
- uses `<button role="switch" aria-checked={checked}>`
- thumb slides on toggle
- clicking calls `onChange(!checked)`

---

## - [ ] 9. Tests

### Feature Tests

**`tests/Feature/Settings/NotificationPreferencesTest.php`**

Test the settings API behavior:

- `it saves notification preferences for the authenticated agent`
- `it returns current preferences for the authenticated agent`
- `it creates default preferences on first fetch if none exist`
- `it returns 403 when the triage gate denies access`
- `it validates that all preference fields must be boolean`
- `it creates separate preferences per user`

Assertions should verify JSON payloads and `422` validation responses, not redirects.

### Unit Tests

**`tests/Unit/Models/AgentPreferenceTest.php`**

- `it uses the correct table name`
- `it casts all preference columns to boolean`
- `it has the expected fillable fields`

---

## - [ ] 10. Frontend Route Update

Update the Phase 6 frontend route tree so:
- `/settings` redirects client-side to `/settings/notifications`
- `/settings/notifications` renders the notification preferences page

This keeps browser URLs aligned with the dashboard information architecture while leaving the server transport minimal.

---

## - [ ] 11. Architecture Diagram Update

The Phase 6 frontend architecture diagram should be updated to include the Settings page and settings API:

```
Settings/Notifications → SettingsNav (uses)
Settings/Notifications → Toggle (uses)
Settings/Notifications → api/settings/notifications (fetches)
app.tsx → Settings/Notifications (routes)
```
