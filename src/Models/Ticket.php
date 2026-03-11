<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Models;

use HotReloadStudios\Triage\Database\Factories\TicketFactory;
use HotReloadStudios\Triage\Enums\TicketPriority;
use HotReloadStudios\Triage\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Ticket extends Model
{
    use HasFactory;
    use HasUuids;

    public $incrementing = false;

    /** @var list<string> */
    protected $fillable = [
        'subject',
        'status',
        'priority',
        'submitter_id',
        'submitter_name',
        'submitter_email',
        'assignee_id',
        'reply_token',
    ];

    protected $keyType = 'string';

    /** @return HasMany<TicketMessage, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class)->orderBy('created_at');
    }

    /** @return HasMany<TicketNote, $this> */
    public function notes(): HasMany
    {
        return $this->hasMany(TicketNote::class)->orderBy('created_at');
    }

    public function submitter(): BelongsTo
    {
        $userModelClass = config('triage.user_model');
        /** @var Model $userModel */
        $userModel = new $userModelClass;

        return $this->belongsTo($userModelClass, 'submitter_id', $userModel->getKeyName());
    }

    public function assignee(): BelongsTo
    {
        $userModelClass = config('triage.user_model');
        /** @var Model $userModel */
        $userModel = new $userModelClass;

        return $this->belongsTo($userModelClass, 'assignee_id', $userModel->getKeyName());
    }

    /** @param Builder<Ticket> $query */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', TicketStatus::Open);
    }

    /** @param Builder<Ticket> $query */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', TicketStatus::Pending);
    }

    /** @param Builder<Ticket> $query */
    public function scopeResolved(Builder $query): Builder
    {
        return $query->where('status', TicketStatus::Resolved);
    }

    /** @param Builder<Ticket> $query */
    public function scopeClosed(Builder $query): Builder
    {
        return $query->where('status', TicketStatus::Closed);
    }

    /** @param Builder<Ticket> $query */
    public function scopeAssignedTo(Builder $query, string $userId): Builder
    {
        return $query->where('assignee_id', $userId);
    }

    /** @param Builder<Ticket> $query */
    public function scopeWithPriority(Builder $query, TicketPriority $priority): Builder
    {
        return $query->where('priority', $priority);
    }

    protected static function newFactory(): TicketFactory
    {
        return TicketFactory::new();
    }

    /** @return array<string, class-string> */
    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
            'priority' => TicketPriority::class,
        ];
    }
}
