<?php

declare(strict_types=1);

use HotReloadStudios\Triage\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(TestCase::class)->in(__DIR__);
uses(RefreshDatabase::class)->in('Feature', 'Unit');
