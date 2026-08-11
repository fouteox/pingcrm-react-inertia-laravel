<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

Schedule::command('demo:reset')
    ->hourly()
    ->environments('production')
    ->onOneServer()
    ->withoutOverlapping((int) ceil(config('demo.reset.lock_seconds') / 60));
