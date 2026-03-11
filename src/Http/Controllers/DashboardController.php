<?php

declare(strict_types=1);

namespace HotReloadStudios\Triage\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

final class DashboardController
{
    public function __invoke(Request $request): View
    {
        return view('triage::app');
    }
}
