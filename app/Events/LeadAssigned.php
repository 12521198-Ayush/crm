<?php

namespace App\Events;

use App\Models\Lead;
use Illuminate\Foundation\Events\Dispatchable;

class LeadAssigned implements AutomationTriggerEvent
{
    use Dispatchable;

    /** @param string $key lead.assigned or lead.reassigned */
    public function __construct(public Lead $leadModel, public int $assignedTo, public string $key = 'lead.assigned') {}

    public function eventKey(): string { return $this->key; }
    public function lead(): Lead { return $this->leadModel; }
    public function context(): array { return ['assigned_to' => $this->assignedTo]; }
}
