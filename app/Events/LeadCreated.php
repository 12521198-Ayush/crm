<?php

namespace App\Events;

use App\Models\Lead;
use Illuminate\Foundation\Events\Dispatchable;

class LeadCreated implements AutomationTriggerEvent
{
    use Dispatchable;

    /** @param string $key lead.created or lead.imported */
    public function __construct(public Lead $leadModel, public string $key = 'lead.created', public array $meta = []) {}

    public function eventKey(): string { return $this->key; }
    public function lead(): Lead { return $this->leadModel; }
    public function context(): array { return $this->meta; }
}
