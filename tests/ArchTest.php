<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Tests;

arch('it will not use debugging functions')
    ->expect(['dd', 'dump', 'ray'])
    ->each->not->toBeUsed();

arch('it ensures models use HasUuids trait')
    ->expect('HotReloadStudios\Triage\Models')
    ->toUseTrait('Illuminate\Database\Eloquent\Concerns\HasUuids');

arch('it ensures enums are string-backed')
    ->expect('HotReloadStudios\Triage\Enums')
    ->toBeStringBackedEnums();
