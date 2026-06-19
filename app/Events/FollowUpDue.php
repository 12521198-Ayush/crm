<?php

namespace App\Events;

use App\Models\Lead;
use Illuminate\Foundation\Events\Dispatchable;

class FollowUpDue implements AutomationTriggerEvent
{
    use Dispatchable;

    /** @param string $key followup.due, followup.overdue or meeting.due */
    public function __construct(public Lead $leadModel, public string $key = 'followup.due') {}

    public function eventKey(): string { return $this->key; }
    public function lead(): Lead { return $this->leadModel; }

    public function context(): array
    {
        return [
            'follow_up_at' => optional($this->leadModel->follow_up_at)->toIso8601String(),
            'meeting_date' => optional($this->leadModel->follow_up_at)?->format('d M Y, h:i A'),
        ];
    }
}
