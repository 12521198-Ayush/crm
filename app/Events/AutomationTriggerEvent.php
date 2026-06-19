<?php

namespace App\Events;

use App\Models\Lead;

/** Contract for any domain event that can drive WhatsApp automations. */
interface AutomationTriggerEvent
{
    public function eventKey(): string;

    public function lead(): Lead;

    public function context(): array;
}
