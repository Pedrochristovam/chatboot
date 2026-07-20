<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('messages:dispatch-scheduled')->everyMinute();
Schedule::command('agents:requeue-offline')->everyMinute()->withoutOverlapping();
Schedule::command('operations:heartbeat')->everyMinute();
Schedule::command('operations:prune')->dailyAt('03:30')->withoutOverlapping();
