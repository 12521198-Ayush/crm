<?php

namespace App\Events;

use App\Models\Lead;
use Illuminate\Foundation\Events\Dispatchable;

class LeadStatusChanged implements AutomationTriggerEvent
{
    use Dispatchable;

    public function __construct(public Lead $leadModel, public ?int $oldStatusId, public int $newStatusId) {}

    public function eventKey(): string { return 'lead.status_changed'; }
    public function lead(): Lead { return $this->leadModel; }

    public function context(): array
    {
        return [
            'old_status_id' => $this->oldStatusId,
            'new_status_id' => $this->newStatusId,
        ];
    }
}
