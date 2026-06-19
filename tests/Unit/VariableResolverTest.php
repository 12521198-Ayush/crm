<?php

namespace Tests\Unit;

use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\Project;
use App\Models\WhatsappTemplate;
use App\Services\Automation\VariableResolver;
use PHPUnit\Framework\TestCase;

class VariableResolverTest extends TestCase
{
    private function lead(): Lead
    {
        // Build a lead with relations pre-set so no DB access is needed.
        $lead = new Lead(['customer_name' => 'John', 'mobile' => '+15551234', 'city' => 'Mumbai']);
        $lead->setRelation('status', new LeadStatus(['name' => 'Interested']));
        $lead->setRelation('project', new Project(['name' => 'Skyline Residency']));
        $lead->setRelation('source', null);
        $lead->setRelation('assignee', null);
        return $lead;
    }

    private function template(string $body, array $bodyPositions): WhatsappTemplate
    {
        return new WhatsappTemplate([
            'name'       => 'interested_tpl',
            'components' => [['type' => 'BODY', 'text' => $body]],
            'variables'  => ['body_count' => count($bodyPositions), 'body' => $bodyPositions],
        ]);
    }

    public function test_body_values_follow_placeholder_order(): void
    {
        $resolver = new VariableResolver();
        $template = $this->template('Hi {{1}}, interested in {{2}}?', [1, 2]);
        $map = ['1' => 'lead_name', '2' => 'project_name'];

        $values = $resolver->bodyValues($template, $this->lead(), $map);

        $this->assertSame(['John', 'Skyline Residency'], $values);
    }

    public function test_unmapped_position_resolves_to_empty_string(): void
    {
        $resolver = new VariableResolver();
        $template = $this->template('Hi {{1}} {{2}}', [1, 2]);
        $values = $resolver->bodyValues($template, $this->lead(), ['1' => 'lead_name']);

        $this->assertSame(['John', ''], $values);
    }

    public function test_preview_renders_tokens_into_body(): void
    {
        $resolver = new VariableResolver();
        $template = $this->template('Hi {{1}}, thanks for your interest in {{2}}.', [1, 2]);
        $map = ['1' => 'lead_name', '2' => 'project_name'];

        $preview = $resolver->preview($template, $this->lead(), $map);

        $this->assertSame('Hi John, thanks for your interest in Skyline Residency.', $preview);
    }
}
