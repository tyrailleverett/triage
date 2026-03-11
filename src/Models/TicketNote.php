<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Models;

use HotReloadStudios\Triage\Database\Factories\TicketNoteFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class TicketNote extends Model
{
    use HasFactory;
    use HasUuids;

    public $incrementing = false;

    /** @var list<string> */
    protected $fillable = [
        'ticket_id',
        'author_id',
        'body',
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

    protected static function newFactory(): TicketNoteFactory
    {
        return TicketNoteFactory::new();
    }
}
