<?php

namespace App\Services\Automation;

use App\Models\Lead;
use App\Models\WhatsappTemplate;

/**
 * Resolves CRM variable tokens ({{lead_name}}, {{agent_name}}, ...) into real
 * values from a lead, and maps them into the ordered body_values a template's
 * numbered placeholders ({{1}}..{{n}}) expect.
 */
class VariableResolver
{
    /** All supported token => value pairs for a lead. */
    public function tokens(Lead $lead, array $context = []): array
    {
        $lead->loadMissing(['status', 'source', 'project', 'assignee']);

        $meetingDate = $lead->follow_up_at ? $lead->follow_up_at->format('d M Y, h:i A') : '';

        return [
            'lead_name'    => $lead->customer_name ?? '',
            'mobile'       => $lead->mobile ?? '',
            'email'        => $lead->email ?? '',
            'source'       => $lead->source?->name ?? '',
            'status'       => $lead->status?->name ?? '',
            'agent_name'   => $lead->assignee?->name ?? '',
            'project_name' => $lead->project?->name ?? '',
            'meeting_date' => $context['meeting_date'] ?? $meetingDate,
            'company_name' => config('whatsupninja.company_name', config('app.name')),
            'city'         => $lead->city ?? '',
        ];
    }

    /**
     * Build the ordered list of body values for a template.
     *
     * $variableMap maps each placeholder position to a token name, e.g.
     *   ['1' => 'lead_name', '2' => 'project_name'].
     * Falls back to the configured variable order if a position is unmapped.
     */
    public function bodyValues(WhatsappTemplate $template, Lead $lead, array $variableMap = [], array $context = []): array
    {
        $tokens = $this->tokens($lead, $context);
        $positions = $template->variables['body'] ?? [];
        sort($positions);

        $values = [];
        foreach ($positions as $pos) {
            $tokenName = $variableMap[(string) $pos] ?? $variableMap[$pos] ?? null;
            $values[] = $tokenName !== null ? ($tokens[$tokenName] ?? '') : '';
        }
        return $values;
    }

    /**
     * Render a human-readable preview of the template body with tokens applied.
     * Used by the builder's live preview panel.
     */
    public function preview(WhatsappTemplate $template, Lead $lead, array $variableMap = [], array $context = []): string
    {
        $bodyText = '';
        foreach ($template->components ?? [] as $c) {
            if (strtoupper($c['type'] ?? '') === 'BODY') {
                $bodyText = $c['text'] ?? '';
                break;
            }
        }

        $tokens = $this->tokens($lead, $context);
        foreach (($template->variables['body'] ?? []) as $pos) {
            $tokenName = $variableMap[(string) $pos] ?? $variableMap[$pos] ?? null;
            $replacement = $tokenName !== null ? ($tokens[$tokenName] ?? '') : "{{{$pos}}}";
            $bodyText = str_replace('{{' . $pos . '}}', $replacement, $bodyText);
        }
        return $bodyText;
    }
}
