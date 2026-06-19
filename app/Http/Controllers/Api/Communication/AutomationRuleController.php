<?php

namespace App\Http\Controllers\Api\Communication;

use App\Http\Controllers\Controller;
use App\Jobs\ExecuteAutomationJob;
use App\Models\AutomationRule;
use App\Models\Lead;
use App\Models\WhatsappTemplate;
use App\Services\Automation\VariableResolver;
use App\Support\LeadScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AutomationRuleController extends Controller
{
    public function index(Request $request)
    {
        $ownerId = $request->user()->accountOwnerId();

        return AutomationRule::with(['primaryAction.template:id,name,category'])
            ->where('owner_id', $ownerId)
            ->orderByDesc('id')
            ->get();
    }

    public function store(Request $request)
    {
        $data = $this->validateRule($request);
        $ownerId = $request->user()->accountOwnerId();
        $this->assertTemplateOwned($data['template_id'], $ownerId);

        $rule = DB::transaction(function () use ($data, $request, $ownerId) {
            $rule = AutomationRule::create([
                'owner_id'      => $ownerId,
                'name'          => $data['name'],
                'description'   => $data['description'] ?? null,
                'trigger_event' => $data['trigger_event'],
                'match_type'    => $data['match_type'] ?? 'all',
                'delay_minutes' => $data['delay_minutes'] ?? 0,
                'graph'         => $data['graph'] ?? null,
                'is_active'     => $data['is_active'] ?? true,
                'created_by'    => $request->user()->id,
            ]);
            $this->syncChildren($rule, $data);
            return $rule;
        });

        return response()->json($this->load($rule), 201);
    }

    public function show(Request $request, AutomationRule $automationRule)
    {
        $this->authorizeRule($request, $automationRule);
        return $this->load($automationRule);
    }

    public function update(Request $request, AutomationRule $automationRule)
    {
        $this->authorizeRule($request, $automationRule);
        $data = $this->validateRule($request);
        $this->assertTemplateOwned($data['template_id'], $automationRule->owner_id);

        DB::transaction(function () use ($automationRule, $data) {
            $automationRule->update([
                'name'          => $data['name'],
                'description'   => $data['description'] ?? null,
                'trigger_event' => $data['trigger_event'],
                'match_type'    => $data['match_type'] ?? 'all',
                'delay_minutes' => $data['delay_minutes'] ?? 0,
                'graph'         => $data['graph'] ?? null,
                'is_active'     => $data['is_active'] ?? true,
            ]);
            $automationRule->conditions()->delete();
            $automationRule->actions()->delete();
            $this->syncChildren($automationRule, $data);
        });

        return $this->load($automationRule->fresh());
    }

    public function destroy(Request $request, AutomationRule $automationRule)
    {
        $this->authorizeRule($request, $automationRule);
        $automationRule->delete();
        return response()->noContent();
    }

    public function toggle(Request $request, AutomationRule $automationRule)
    {
        $this->authorizeRule($request, $automationRule);
        $automationRule->update(['is_active' => ! $automationRule->is_active]);
        return $this->load($automationRule->fresh());
    }

    /** Render a live variable preview against a sample (or chosen) lead. */
    public function preview(Request $request, VariableResolver $resolver)
    {
        $ownerId = $request->user()->accountOwnerId();
        $data = $request->validate([
            'template_id'  => 'required|integer',
            'variable_map' => 'nullable|array',
            'lead_id'      => 'nullable|integer',
        ]);

        $template = WhatsappTemplate::where('owner_id', $ownerId)->findOrFail($data['template_id']);
        $lead = $this->sampleLead($request, $data['lead_id'] ?? null);

        return response()->json([
            'preview' => $resolver->preview($template, $lead, $data['variable_map'] ?? []),
            'values'  => $resolver->bodyValues($template, $lead, $data['variable_map'] ?? []),
        ]);
    }

    /** Fire the rule immediately against one lead (dispatches the real send job). */
    public function testRun(Request $request, AutomationRule $automationRule)
    {
        $this->authorizeRule($request, $automationRule);
        $data = $request->validate(['lead_id' => 'required|exists:leads,id']);

        $lead = Lead::findOrFail($data['lead_id']);
        ExecuteAutomationJob::dispatch(
            $automationRule->id,
            $lead->id,
            'manual.test',
            [],
            'test:' . $automationRule->id . ':' . $lead->id . ':' . now()->timestamp,
        );

        return response()->json(['success' => true, 'message' => 'Test send queued']);
    }

    // ---- helpers ---------------------------------------------------------

    private function validateRule(Request $request): array
    {
        return $request->validate([
            'name'                    => 'required|string|max:191',
            'description'             => 'nullable|string',
            'trigger_event'           => 'required|string|max:64',
            'match_type'              => 'nullable|in:all,any',
            'delay_minutes'           => 'nullable|integer|min:0|max:10080',
            'is_active'               => 'nullable|boolean',
            'template_id'             => 'required|integer',
            'variable_map'            => 'nullable|array',
            'button_values'           => 'nullable|array',
            'graph'                   => 'nullable|array',
            'conditions'              => 'nullable|array',
            'conditions.*.field'      => 'required_with:conditions|string',
            'conditions.*.operator'   => 'required_with:conditions|string',
            'conditions.*.value'      => 'nullable',
        ]);
    }

    private function syncChildren(AutomationRule $rule, array $data): void
    {
        foreach (($data['conditions'] ?? []) as $i => $c) {
            $rule->conditions()->create([
                'field'    => $c['field'],
                'operator' => $c['operator'],
                'value'    => array_key_exists('value', $c) ? (array) $c['value'] : null,
                'sort'     => $i,
            ]);
        }

        $rule->actions()->create([
            'type'        => 'send_whatsapp_template',
            'template_id' => $data['template_id'],
            'config'      => [
                'variable_map'  => $data['variable_map'] ?? [],
                'button_values' => $data['button_values'] ?? [],
                'delay_minutes' => $data['delay_minutes'] ?? 0,
            ],
            'sort'        => 0,
        ]);
    }

    private function load(AutomationRule $rule): AutomationRule
    {
        return $rule->load(['conditions', 'primaryAction.template:id,name,category,language,variables']);
    }

    private function assertTemplateOwned(int $templateId, int $ownerId): void
    {
        abort_unless(
            WhatsappTemplate::where('owner_id', $ownerId)->whereKey($templateId)->exists(),
            422,
            'Template does not belong to this account'
        );
    }

    private function authorizeRule(Request $request, AutomationRule $rule): void
    {
        abort_unless($rule->owner_id === $request->user()->accountOwnerId(), 403);
    }

    private function sampleLead(Request $request, ?int $leadId): Lead
    {
        if ($leadId) {
            $lead = Lead::find($leadId);
            if ($lead) return $lead;
        }
        $scoped = Lead::query();
        LeadScope::apply($scoped, $request->user());
        $lead = $scoped->latest('id')->first();

        // Fall back to an unsaved sample so preview always renders.
        return $lead ?? new Lead([
            'customer_name' => 'John Sample',
            'mobile'        => '+10000000000',
            'email'         => 'john@example.com',
            'city'          => 'Mumbai',
        ]);
    }
}
