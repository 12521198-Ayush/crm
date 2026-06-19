<?php

namespace App\Services\Automation;

use App\Jobs\ExecuteAutomationJob;
use App\Models\AutomationRule;
use App\Models\Lead;

/**
 * Entry point for the automation engine. Given a domain event, finds matching
 * active rules for the lead's tenant and queues a send job for each.
 */
class AutomationDispatcher
{
    public function __construct(private ConditionEvaluator $evaluator) {}

    public function fire(string $eventKey, Lead $lead, array $context = []): int
    {
        $ownerId = $this->ownerIdFor($lead);
        if (! $ownerId || ! $lead->mobile) {
            return 0;
        }

        $rules = AutomationRule::with('conditions')
            ->where('owner_id', $ownerId)
            ->where('trigger_event', $eventKey)
            ->where('is_active', true)
            ->get();

        $queued = 0;
        foreach ($rules as $rule) {
            if (! $this->evaluator->passes($rule, $lead, $context)) {
                continue;
            }

            $dedupeKey = $this->dedupeKey($rule, $lead, $eventKey, $context);

            ExecuteAutomationJob::dispatch($rule->id, $lead->id, $eventKey, $context, $dedupeKey)
                ->delay(now()->addMinutes((int) $rule->delay_minutes));

            $queued++;
        }

        return $queued;
    }

    /**
     * Resolve the tenant owner for a lead. Leads carry no owner_id, so derive it
     * from the assignee or creator (their account owner). Falls back to the sole
     * connection owner if neither is set.
     */
    private function ownerIdFor(Lead $lead): ?int
    {
        $user = $lead->assignee ?: $lead->creator;
        if ($user) {
            return $user->accountOwnerId();
        }
        return \App\Models\WhatsupninjaConnection::where('is_active', true)->value('owner_id');
    }

    /**
     * Stable key so the same event occurrence never sends twice (e.g. a
     * follow-up scanner running every 5 minutes, or a retried request).
     */
    private function dedupeKey(AutomationRule $rule, Lead $lead, string $eventKey, array $context): string
    {
        $occurrence = $context['new_status_id']
            ?? $context['occurrence']
            ?? ($context['follow_up_at'] ?? null)
            ?? '';

        return implode(':', ['rule', $rule->id, 'lead', $lead->id, $eventKey, $occurrence]);
    }
}
