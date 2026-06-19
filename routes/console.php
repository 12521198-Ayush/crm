<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// WhatsApp automation engine schedules.
Schedule::command('whatsupninja:scan-followups')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('whatsupninja:sync-templates')
    ->cron('0 */' . max(1, (int) config('whatsupninja.sync_interval_hours', 6)) . ' * * *')
    ->withoutOverlapping();
