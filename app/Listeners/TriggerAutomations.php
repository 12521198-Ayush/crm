<?php

namespace App\Listeners;

use App\Events\AutomationTriggerEvent;
use App\Services\Automation\AutomationDispatcher;

class TriggerAutomations
{
    public function __construct(private AutomationDispatcher $dispatcher) {}

    public function handle(AutomationTriggerEvent $event): void
    {
        $this->dispatcher->fire($event->eventKey(), $event->lead(), $event->context());
    }
}
