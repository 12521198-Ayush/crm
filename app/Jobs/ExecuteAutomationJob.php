<?php

namespace App\Jobs;

use App\Models\AutomationRule;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\WhatsappMessageLog;
use App\Models\WhatsupninjaConnection;
use App\Services\Automation\ConditionEvaluator;
use App\Services\Automation\VariableResolver;
use App\Services\WhatsupNinja\WhatsupNinjaClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ExecuteAutomationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [30, 120];

    public function __construct(
        public int $ruleId,
        public int $leadId,
        public string $eventKey,
        public array $context = [],
        public ?string $dedupeKey = null,
    ) {}

    public function handle(ConditionEvaluator $evaluator, VariableResolver $resolver): void
    {
        $rule = AutomationRule::with(['conditions', 'primaryAction.template'])->find($this->ruleId);
        $lead = Lead::find($this->leadId);
        if (! $rule || ! $lead || ! $rule->is_active || ! $lead->mobile) {
            return;
        }

        // Skip if this exact occurrence was already sent (idempotency).
        if ($this->dedupeKey && WhatsappMessageLog::query()
            ->where('rule_id', $rule->id)
            ->where('lead_id', $lead->id)
            ->whereIn('status', [
                WhatsappMessageLog::STATUS_QUEUED, WhatsappMessageLog::STATUS_SENT,
                WhatsappMessageLog::STATUS_DELIVERED, WhatsappMessageLog::STATUS_READ,
            ])
            ->whereJsonContains('variables->_dedupe', $this->dedupeKey)
            ->exists()) {
            return;
        }

        // Re-check conditions at execution time (state may have changed during the delay).
        if (! $evaluator->passes($rule, $lead, $this->context)) {
            return;
        }

        $action   = $rule->primaryAction;
        $template = $action?->template;
        $connection = WhatsupninjaConnection::where('owner_id', $rule->owner_id)->first();

        if (! $template || ! $connection || ! $connection->isConnected()) {
            $this->logFailure($rule, $lead, $template, $connection, 'Connection or template unavailable');
            return;
        }

        $variableMap = $action->config['variable_map'] ?? [];
        $bodyValues  = $resolver->bodyValues($template, $lead, $variableMap, $this->context);

        $log = WhatsappMessageLog::create([
            'owner_id'      => $rule->owner_id,
            'lead_id'       => $lead->id,
            'rule_id'       => $rule->id,
            'connection_id' => $connection->id,
            'template_id'   => $template->id,
            'trigger_event' => $this->eventKey,
            'recipient'     => $lead->mobile,
            'variables'     => ['values' => $bodyValues, '_dedupe' => $this->dedupeKey],
            'status'        => WhatsappMessageLog::STATUS_QUEUED,
            'queued_at'     => now(),
        ]);

        try {
            $result = (new WhatsupNinjaClient($connection))->sendTemplateByPhone(
                $lead->mobile,
                $template->name,
                $bodyValues,
                [],
                $lead->customer_name,
            );

            $log->forceFill([
                'status'            => WhatsappMessageLog::STATUS_SENT,
                'remote_message_id' => $result['message_id'],
                'sent_at'           => now(),
                'response_payload'  => $result['response'],
            ])->save();

            $rule->forceFill([
                'executions_count' => $rule->executions_count + 1,
                'last_run_at'      => now(),
            ])->save();

            $this->timeline($lead, $template, 'sent', $log->id);
        } catch (Throwable $e) {
            $log->forceFill([
                'status'        => WhatsappMessageLog::STATUS_FAILED,
                'error_message' => $e->getMessage(),
                'failed_at'     => now(),
            ])->save();
            $this->timeline($lead, $template, 'failed', $log->id, $e->getMessage());
            throw $e; // allow retry/backoff
        }
    }

    private function logFailure($rule, Lead $lead, $template, $connection, string $reason): void
    {
        WhatsappMessageLog::create([
            'owner_id'      => $rule->owner_id,
            'lead_id'       => $lead->id,
            'rule_id'       => $rule->id,
            'connection_id' => $connection?->id,
            'template_id'   => $template?->id,
            'trigger_event' => $this->eventKey,
            'recipient'     => $lead->mobile,
            'status'        => WhatsappMessageLog::STATUS_FAILED,
            'error_message' => $reason,
            'failed_at'     => now(),
        ]);
    }

    private function timeline(Lead $lead, $template, string $status, int $logId, ?string $error = null): void
    {
        LeadActivity::create([
            'lead_id' => $lead->id,
            'type'    => LeadActivity::TYPE_WHATSAPP,
            'title'   => 'WhatsApp template ' . $status,
            'body'    => $error,
            'meta'    => [
                'template'       => $template?->name,
                'status'         => $status,
                'message_log_id' => $logId,
                'trigger'        => $this->eventKey,
            ],
        ]);
    }
}
