<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Support\LeadScope;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $base = fn() => LeadScope::apply(Lead::query(), $user);

        return [
            'cards' => $this->cards($base),
            'by_source' => (clone $base())
                ->selectRaw('source_id, COUNT(*) as c')
                ->groupBy('source_id')
                ->with('source:id,name,slug,channel')
                ->get()
                ->map(fn($r) => [
                    'source' => optional($r->source)->name ?? 'Unknown',
                    'source_id' => $r->source_id,
                    'source_slug' => optional($r->source)->slug,
                    'count'  => (int) $r->c,
                ]),
            'by_sub_source' => (clone $base())
                ->selectRaw('sub_source, COUNT(*) as c')
                ->whereNotNull('sub_source')
                ->groupBy('sub_source')
                ->orderByDesc('c')
                ->limit(8)
                ->get()
                ->map(fn($r) => [
                    'sub_source' => $r->sub_source,
                    'count' => (int) $r->c,
                ]),
            'by_status' => (clone $base())
                ->selectRaw('status_id, COUNT(*) as c')
                ->groupBy('status_id')
                ->with('status:id,name,slug,color')
                ->get()
                ->map(fn($r) => [
                    'status' => optional($r->status)->name ?? 'Unknown',
                    'status_id' => $r->status_id,
                    'status_slug' => optional($r->status)->slug,
                    'color'  => optional($r->status)->color ?? '#94a3b8',
                    'count'  => (int) $r->c,
                ]),
            'by_agent' => (clone $base())
                ->selectRaw('assigned_to, COUNT(*) as c')
                ->groupBy('assigned_to')
                ->with('assignee:id,name')
                ->orderByDesc('c')
                ->limit(8)
                ->get()
                ->map(fn($r) => [
                    'agent' => optional($r->assignee)->name ?? 'Unassigned',
                    'count' => (int) $r->c,
                ]),
        ];
    }

    private function cards(callable $base): array
    {
        $statusCount = fn (array $slugs) => (clone $base())->whereHas('status', fn ($q) => $q->whereIn('slug', $slugs))->count();

        return [
            ['label' => 'Total Leads', 'value' => (clone $base())->count(), 'icon' => 'leads', 'tone' => 'brand', 'query' => []],
            ['label' => 'Unassigned Leads', 'value' => (clone $base())->whereNull('assigned_to')->count(), 'icon' => 'user', 'tone' => 'slate', 'query' => ['assigned' => 'unassigned']],
            ['label' => 'New Leads', 'value' => $statusCount(['new', 'fresh', 'untouched']), 'icon' => 'plus', 'tone' => 'emerald', 'query' => ['status_slug' => 'new,fresh,untouched']],
            ['label' => 'Pending Leads', 'value' => $statusCount(['pending', 'follow-up']), 'icon' => 'clock', 'tone' => 'amber', 'query' => ['status_slug' => 'pending,follow-up']],
            ['label' => 'Callbacks', 'value' => (clone $base())->whereNotNull('follow_up_at')->count(), 'icon' => 'phone', 'tone' => 'indigo', 'query' => ['due' => 'callbacks']],
            ['label' => 'Meetings Scheduled', 'value' => $statusCount(['meeting-scheduled', 'meeting']), 'icon' => 'calendar', 'tone' => 'cyan', 'query' => ['status_slug' => 'meeting-scheduled,meeting']],
            ['label' => 'Not Interested', 'value' => $statusCount(['not-interested']), 'icon' => 'x', 'tone' => 'rose', 'query' => ['status_slug' => 'not-interested']],
            ['label' => 'Dropped Leads', 'value' => $statusCount(['dropped', 'closed']), 'icon' => 'archive', 'tone' => 'slate', 'query' => ['status_slug' => 'dropped,closed']],
        ];
    }
}
