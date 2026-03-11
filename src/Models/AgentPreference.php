<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $user_id
 * @property bool $notify_ticket_assigned
 * @property bool $notify_ticket_replied
 * @property bool $notify_note_added
 * @property bool $notify_status_changed
 * @property bool $daily_digest
 * @property bool $email_notifications
 */
final class AgentPreference extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $table = 'triage_agent_preferences';

    protected $keyType = 'string';

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'notify_ticket_assigned',
        'notify_ticket_replied',
        'notify_note_added',
        'notify_status_changed',
        'daily_digest',
        'email_notifications',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'notify_ticket_assigned' => 'boolean',
        'notify_ticket_replied' => 'boolean',
        'notify_note_added' => 'boolean',
        'notify_status_changed' => 'boolean',
        'daily_digest' => 'boolean',
        'email_notifications' => 'boolean',
    ];
}
