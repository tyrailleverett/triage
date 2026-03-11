<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Models;

use HotReloadStudios\Triage\Database\Factories\TicketMessageFactory;
use HotReloadStudios\Triage\Enums\MessageDirection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class TicketMessage extends Model
{
    use HasFactory;
    use HasUuids;

    public $incrementing = false;

    /** @var list<string> */
    protected $fillable = [
        'ticket_id',
        'direction',
        'author_id',
        'message_id',
        'body',
        'raw_email',
    ];

    protected $keyType = 'string';

    /** @return BelongsTo<Ticket, $this> */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function author(): BelongsTo
    {
        $userModelClass = config('triage.user_model');
        /** @var Model $userModel */
        $userModel = new $userModelClass;

        return $this->belongsTo($userModelClass, 'author_id', $userModel->getKeyName());
    }

    /** @param Builder<TicketMessage> $query */
    public function scopeInbound(Builder $query): Builder
    {
        return $query->where('direction', MessageDirection::Inbound);
    }

    /** @param Builder<TicketMessage> $query */
    public function scopeOutbound(Builder $query): Builder
    {
        return $query->where('direction', MessageDirection::Outbound);
    }

    protected static function newFactory(): TicketMessageFactory
    {
        return TicketMessageFactory::new();
    }

    /** @return array<string, class-string> */
    protected function casts(): array
    {
        return [
            'direction' => MessageDirection::class,
        ];
    }
}
