<?php

namespace App\Services\Automation;

use App\Models\AutomationRule;
use App\Models\Lead;

/**
 * Evaluates an automation rule's conditions against a lead and event context.
 *
 * $context may carry event-specific data, e.g.:
 *   - new_status_id / old_status_id  (lead.status_changed)
 *   - assigned_to                    (lead.assigned / reassigned)
 */
class ConditionEvaluator
{
    public function passes(AutomationRule $rule, Lead $lead, array $context = []): bool
    {
        $conditions = $rule->relationLoaded('conditions') ? $rule->conditions : $rule->conditions()->get();
        if ($conditions->isEmpty()) {
            return true; // no conditions = always matches
        }

        $results = $conditions->map(fn ($c) => $this->evaluate($c->field, $c->operator, $c->value, $lead, $context));

        return $rule->match_type === 'any' ? $results->contains(true) : ! $results->contains(false);
    }

    public function evaluate(string $field, string $operator, $value, Lead $lead, array $context = []): bool
    {
        $actual = $this->fieldValue($field, $lead, $context);
        $expected = $this->normalizeValue($value);

        return match ($operator) {
            'equals'     => $this->scalar($actual) === $this->scalar($expected),
            'not_equals' => $this->scalar($actual) !== $this->scalar($expected),
            'in'         => in_array($this->scalar($actual), $this->toList($expected), true),
            'not_in'     => ! in_array($this->scalar($actual), $this->toList($expected), true),
            'changed_to' => $this->scalar($context['new_status_id'] ?? $actual) === $this->scalar($expected),
            'is_set'     => $actual !== null && $actual !== '',
            'is_empty'   => $actual === null || $actual === '',
            'contains'   => $actual !== null
                && str_contains(mb_strtolower((string) $actual), mb_strtolower((string) $this->scalar($expected))),
            default      => false,
        };
    }

    private function fieldValue(string $field, Lead $lead, array $context)
    {
        // Event context wins for status (so changed_to sees the new value).
        if ($field === 'status_id' && array_key_exists('new_status_id', $context)) {
            return $context['new_status_id'];
        }
        if ($field === 'assigned_to' && array_key_exists('assigned_to', $context)) {
            return $context['assigned_to'];
        }
        return $lead->{$field} ?? null;
    }

    private function normalizeValue($value)
    {
        // Conditions store value as JSON; a single value may arrive as ['x'] or 'x'.
        if (is_array($value) && count($value) === 1 && array_key_exists(0, $value)) {
            return $value[0];
        }
        return $value;
    }

    private function toList($value): array
    {
        return array_map([$this, 'scalar'], is_array($value) ? $value : [$value]);
    }

    private function scalar($value): string
    {
        return is_bool($value) ? ($value ? '1' : '0') : (string) ($value ?? '');
    }
}
