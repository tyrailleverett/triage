<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Tests\Unit\Models;

use HotReloadStudios\Triage\Models\AgentPreference;

it('uses the correct table name', function (): void {
    $model = new AgentPreference;

    expect($model->getTable())->toBe('triage_agent_preferences');
});

it('casts all preference columns to boolean', function (): void {
    $casts = (new AgentPreference)->getCasts();

    expect($casts)->toHaveKey('notify_ticket_assigned')
        ->and($casts['notify_ticket_assigned'])->toBe('boolean')
        ->and($casts)->toHaveKey('notify_ticket_replied')
        ->and($casts['notify_ticket_replied'])->toBe('boolean')
        ->and($casts)->toHaveKey('notify_note_added')
        ->and($casts['notify_note_added'])->toBe('boolean')
        ->and($casts)->toHaveKey('notify_status_changed')
        ->and($casts['notify_status_changed'])->toBe('boolean')
        ->and($casts)->toHaveKey('daily_digest')
        ->and($casts['daily_digest'])->toBe('boolean')
        ->and($casts)->toHaveKey('email_notifications')
        ->and($casts['email_notifications'])->toBe('boolean');
});

it('has the expected fillable fields', function (): void {
    $fillable = (new AgentPreference)->getFillable();

    expect($fillable)->toContain('user_id')
        ->and($fillable)->toContain('notify_ticket_assigned')
        ->and($fillable)->toContain('notify_ticket_replied')
        ->and($fillable)->toContain('notify_note_added')
        ->and($fillable)->toContain('notify_status_changed')
        ->and($fillable)->toContain('daily_digest')
        ->and($fillable)->toContain('email_notifications');
});
