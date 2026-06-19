<?php

namespace App\Providers;

use App\Events\FollowUpDue;
use App\Events\LeadAssigned;
use App\Events\LeadCreated;
use App\Events\LeadStatusChanged;
use App\Listeners\TriggerAutomations;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        // Route lead domain events into the WhatsApp automation engine.
        foreach ([LeadCreated::class, LeadStatusChanged::class, LeadAssigned::class, FollowUpDue::class] as $event) {
            Event::listen($event, TriggerAutomations::class);
        }
    }
}
