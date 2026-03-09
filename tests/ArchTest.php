<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Tests;

arch('it will not use debugging functions')
    ->expect(['dd', 'dump', 'ray'])
    ->each->not->toBeUsed();
