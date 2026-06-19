<?php

namespace Tests\Unit;

use App\Models\Lead;
use App\Services\Automation\ConditionEvaluator;
use PHPUnit\Framework\TestCase;

class ConditionEvaluatorTest extends TestCase
{
    private ConditionEvaluator $evaluator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->evaluator = new ConditionEvaluator();
    }

    public function test_equals_matches_lead_attribute(): void
    {
        $lead = new Lead(['city' => 'Mumbai']);
        $this->assertTrue($this->evaluator->evaluate('city', 'equals', 'Mumbai', $lead));
        $this->assertFalse($this->evaluator->evaluate('city', 'equals', 'Delhi', $lead));
    }

    public function test_in_operator_with_list(): void
    {
        $lead = new Lead(['status_id' => 3]);
        $this->assertTrue($this->evaluator->evaluate('status_id', 'in', [1, 2, 3], $lead));
        $this->assertFalse($this->evaluator->evaluate('status_id', 'in', [1, 2], $lead));
    }

    public function test_changed_to_uses_new_status_from_context(): void
    {
        $lead = new Lead(['status_id' => 1]);
        $context = ['new_status_id' => 5];
        $this->assertTrue($this->evaluator->evaluate('status_id', 'changed_to', 5, $lead, $context));
        $this->assertFalse($this->evaluator->evaluate('status_id', 'changed_to', 9, $lead, $context));
    }

    public function test_is_empty_and_is_set(): void
    {
        $lead = new Lead(['email' => null, 'mobile' => '+15551234']);
        $this->assertTrue($this->evaluator->evaluate('email', 'is_empty', null, $lead));
        $this->assertTrue($this->evaluator->evaluate('mobile', 'is_set', null, $lead));
        $this->assertFalse($this->evaluator->evaluate('mobile', 'is_empty', null, $lead));
    }

    public function test_contains_is_case_insensitive(): void
    {
        $lead = new Lead(['customer_name' => 'John Doe']);
        $this->assertTrue($this->evaluator->evaluate('customer_name', 'contains', 'john', $lead));
        $this->assertFalse($this->evaluator->evaluate('customer_name', 'contains', 'smith', $lead));
    }

    public function test_single_value_arrives_wrapped_in_array(): void
    {
        // Conditions persist value as JSON array; a single value comes back as ['Mumbai'].
        $lead = new Lead(['city' => 'Mumbai']);
        $this->assertTrue($this->evaluator->evaluate('city', 'equals', ['Mumbai'], $lead));
    }
}
