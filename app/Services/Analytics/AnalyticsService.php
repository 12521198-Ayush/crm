<?php

namespace App\Services\Analytics;

use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\User;
use App\Services\Analytics\AnalyticsConfig;
use App\Support\LeadScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Central analytics engine. Every query is role-scoped through LeadScope, so
 * the same service safely powers the dashboard, drill-down reports, agent /
 * team / executive reports and (future) custom dashboards.
 */
class AnalyticsService
{
    public function __construct(protected User $user) {}

    /** Fresh, role-scoped Lead query (the single source of visibility). */
    protected function scoped(): Builder
    {
        return LeadScope::apply(Lead::query(), $this->user);
    }

    /* ───────────────────────── Summary cards ───────────────────────── */

    public function summaryCards(AnalyticsFilters $f): array
    {
        $statusGroup = config('analytics.card_groups', AnalyticsConfig::cardGroups());

        $defs = [
            ['key' => 'total',          'label' => 'Total Leads',       'icon' => 'leads',    'tone' => 'brand',   'q' => fn ($q) => $q,                                                       'query' => []],
            ['key' => 'unassigned',     'label' => 'Unassigned Leads',  'icon' => 'user',     'tone' => 'slate',   'q' => fn ($q) => $q->whereNull('assigned_to'),                             'query' => ['assigned' => 'unassigned'], 'invert' => true],
            ['key' => 'new',            'label' => 'New Leads',         'icon' => 'plus',     'tone' => 'info',    'q' => fn ($q) => $this->withStatus($q, $statusGroup['new']),               'query' => ['status_slug' => implode(',', $statusGroup['new'])]],
            ['key' => 'pending',        'label' => 'Pending Leads',     'icon' => 'clock',    'tone' => 'warning', 'q' => fn ($q) => $this->withStatus($q, $statusGroup['pending']),           'query' => ['status_slug' => implode(',', $statusGroup['pending'])]],
            ['key' => 'callbacks',      'label' => 'Callbacks',         'icon' => 'phone',    'tone' => 'indigo',  'q' => fn ($q) => $q->whereNotNull('follow_up_at'),                         'query' => ['due' => 'callbacks']],
            ['key' => 'meetings',       'label' => 'Meetings Scheduled','icon' => 'calendar', 'tone' => 'cyan',    'q' => fn ($q) => $this->withStatus($q, $statusGroup['meeting']),           'query' => ['status_slug' => implode(',', $statusGroup['meeting'])]],
            ['key' => 'not_interested', 'label' => 'Not Interested',    'icon' => 'x',        'tone' => 'danger',  'q' => fn ($q) => $this->withStatus($q, $statusGroup['not_interested']),    'query' => ['status_slug' => implode(',', $statusGroup['not_interested'])], 'invert' => true],
            ['key' => 'dropped',        'label' => 'Dropped Leads',     'icon' => 'archive',  'tone' => 'slate',   'q' => fn ($q) => $this->withStatus($q, $statusGroup['dropped']),           'query' => ['status_slug' => implode(',', $statusGroup['dropped'])], 'invert' => true],
        ];

        return array_map(function ($d) use ($f) {
            $factory = fn () => $d['q']($this->scoped());
            $cmp = PeriodComparator::compare($factory, $f);
            return [
                'key'         => $d['key'],
                'label'       => $d['label'],
                'icon'        => $d['icon'],
                'tone'        => $d['tone'],
                'value'       => $cmp['value'],
                'previous'    => $cmp['previous'],
                'trend_pct'   => $cmp['trend_pct'],
                'direction'   => $cmp['direction'],
                'invert'      => $d['invert'] ?? false,
                'compared_to' => $f->comparisonLabel(),
                'query'       => array_merge($d['query'], $f->toQuery()),
            ];
        }, $defs);
    }

    /* ───────────────────────── Funnel ───────────────────────── */

    public function funnel(AnalyticsFilters $f): array
    {
        return FunnelBuilder::build(fn () => $f->apply($this->scoped()));
    }

    /* ───────────────────────── Source performance ───────────────────────── */

    public function sourcePerformance(AnalyticsFilters $f): array
    {
        $won = config('analytics.won_slugs', AnalyticsConfig::wonSlugs());
        $rows = $f->apply($this->scoped())
            ->selectRaw('source_id, COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN budget IS NOT NULL THEN budget ELSE 0 END) as revenue')
            ->groupBy('source_id')
            ->with('source:id,name,slug,channel')
            ->get()
            ->map(function ($r) use ($f, $won) {
                $converted = $this->withStatus($f->apply($this->scoped()), $won)
                    ->where('source_id', $r->source_id)->count();
                $total = (int) $r->total;
                return [
                    'source_id'      => $r->source_id,
                    'source'         => optional($r->source)->name ?? 'Unknown',
                    'total'          => $total,
                    'converted'      => $converted,
                    'conversion'     => $total > 0 ? round(($converted / $total) * 100, 1) : 0.0,
                    'revenue'        => round((float) $r->revenue, 2), // proxy = sum(budget); future-ready
                    'cost_per_lead'  => null,                          // future-ready (needs ad-spend feed)
                    'query'          => array_merge(['source_id' => $r->source_id], $f->toQuery()),
                ];
            })
            ->sortByDesc('total')
            ->values();

        $best = $rows->where('total', '>=', 1)->sortByDesc('conversion')->first();

        return [
            'rows'           => $rows,
            'best_source'    => $best ? ['source' => $best['source'], 'conversion' => $best['conversion']] : null,
            'total_revenue'  => round($rows->sum('revenue'), 2),
        ];
    }

    /* ───────────────────────── Agent performance / leaderboard ───────────────────────── */

    public function agentPerformance(AnalyticsFilters $f, int $limit = 10): array
    {
        $won = config('analytics.won_slugs', AnalyticsConfig::wonSlugs());
        $meeting = (config('analytics.card_groups', AnalyticsConfig::cardGroups()))['meeting'];

        $agents = $f->apply($this->scoped())
            ->whereNotNull('assigned_to')
            ->selectRaw('assigned_to, COUNT(*) as assigned')
            ->groupBy('assigned_to')
            ->with('assignee:id,name,role')
            ->get();

        $responseByAgent = $this->responseTimesByAgent($f);

        $rows = $agents->map(function ($r) use ($f, $won, $meeting, $responseByAgent) {
            $base = fn () => $f->apply($this->scoped())->where('assigned_to', $r->assigned_to);
            $assigned = (int) $r->assigned;
            $converted = $this->withStatus($base(), $won)->count();
            $meetings  = $this->withStatus($base(), $meeting)->count();
            $followUps = LeadActivity::where('user_id', $r->assigned_to)
                ->where('type', LeadActivity::TYPE_FOLLOWUP)->count();

            return [
                'agent_id'        => $r->assigned_to,
                'agent'           => optional($r->assignee)->name ?? 'Unassigned',
                'assigned'        => $assigned,
                'converted'       => $converted,
                'conversion'      => $assigned > 0 ? round(($converted / $assigned) * 100, 1) : 0.0,
                'meetings'        => $meetings,
                'follow_ups'      => $followUps,
                'response_hours'  => $responseByAgent[$r->assigned_to] ?? null,
                'query'           => array_merge(['assigned_to' => $r->assigned_to], $f->toQuery()),
            ];
        })
        ->sortByDesc(fn ($a) => [$a['converted'], $a['conversion'], $a['assigned']])
        ->values();

        // Rank after sorting.
        $rows = $rows->map(fn ($a, $i) => array_merge($a, ['rank' => $i + 1]));

        return $rows->take($limit)->values()->all();
    }

    /** Avg hours from lead creation to first contact activity, per agent. */
    protected function responseTimesByAgent(AnalyticsFilters $f): array
    {
        $contactTypes = [LeadActivity::TYPE_CALL, LeadActivity::TYPE_WHATSAPP, LeadActivity::TYPE_MEETING, LeadActivity::TYPE_NOTE];

        $firsts = LeadActivity::query()
            ->whereIn('type', $contactTypes)
            ->selectRaw('lead_id, MIN(created_at) as first_at')
            ->groupBy('lead_id')
            ->pluck('first_at', 'lead_id');

        if ($firsts->isEmpty()) return [];

        $leads = $f->apply($this->scoped())
            ->whereNotNull('assigned_to')
            ->whereIn('id', $firsts->keys())
            ->get(['id', 'assigned_to', 'created_at']);

        $acc = [];
        foreach ($leads as $lead) {
            $first = Carbon::parse($firsts[$lead->id]);
            $hours = $lead->created_at->diffInMinutes($first, false) / 60;
            if ($hours < 0) continue;
            $acc[$lead->assigned_to]['sum'] = ($acc[$lead->assigned_to]['sum'] ?? 0) + $hours;
            $acc[$lead->assigned_to]['n']   = ($acc[$lead->assigned_to]['n'] ?? 0) + 1;
        }

        return collect($acc)->map(fn ($a) => round($a['sum'] / max(1, $a['n']), 1))->all();
    }

    /* ───────────────────────── Team performance ───────────────────────── */

    public function teamPerformance(AnalyticsFilters $f): array
    {
        $won = config('analytics.won_slugs', AnalyticsConfig::wonSlugs());

        // Teams = sub_masters visible to the current user.
        $subMasterQuery = User::where('role', User::ROLE_SUB_MASTER);
        if (! ($this->user->isMaster() || $this->user->isSuperMaster())) {
            $subMasterQuery->whereIn('id', $this->user->teamUserIds());
        }
        $subMasters = $subMasterQuery->get(['id', 'name']);

        $rows = $subMasters->map(function ($sm) use ($f, $won) {
            $teamIds = $sm->teamUserIds();
            $base = fn () => $f->apply($this->scoped())
                ->where(fn ($q) => $q->whereIn('assigned_to', $teamIds)->orWhereIn('created_by', $teamIds));
            $volume = $base()->count();
            $converted = $this->withStatus($base(), $won)->count();
            $conversion = $volume > 0 ? round(($converted / $volume) * 100, 1) : 0.0;
            return [
                'team_id'    => $sm->id,
                'team'       => $sm->name . "'s Team",
                'volume'     => $volume,
                'converted'  => $converted,
                'conversion' => $conversion,
                'efficiency' => $this->efficiencyScore($volume, $conversion),
            ];
        })
        ->filter(fn ($t) => $t['volume'] > 0)
        ->sortByDesc('conversion')
        ->values()
        ->all();

        return $rows;
    }

    /** 0–100 blended score: conversion weighted, lightly boosted by volume. */
    protected function efficiencyScore(int $volume, float $conversion): float
    {
        $volumeFactor = min(1, log10(max(1, $volume)) / 2); // saturates ~100 leads
        return round(($conversion * 0.8) + ($volumeFactor * 20), 1);
    }

    /* ───────────────────────── Lead aging ───────────────────────── */

    public function leadAging(AnalyticsFilters $f): array
    {
        $lost = config('analytics.lost_slugs', AnalyticsConfig::lostSlugs());
        $won  = config('analytics.won_slugs', AnalyticsConfig::wonSlugs());
        $closedSlugs = array_merge($lost, $won);

        return collect(config('analytics.aging_buckets', AnalyticsConfig::agingBuckets()))->map(function ($b) use ($f, $closedSlugs) {
            $q = $f->apply($this->scoped(), withDate: false)
                // open leads only (not won/lost)
                ->whereDoesntHave('status', fn ($s) => $s->whereIn('slug', $closedSlugs));

            $now = Carbon::now();
            $maxDate = $now->copy()->subDays($b['min'])->endOfDay();          // newer bound
            $q->where('created_at', '<=', $maxDate);
            if ($b['max'] !== null) {
                $minDate = $now->copy()->subDays($b['max'])->startOfDay();    // older bound
                $q->where('created_at', '>=', $minDate);
            }

            return [
                'key'   => $b['key'],
                'label' => $b['label'],
                'count' => (int) $q->count(),
                'query' => array_merge(['age_min' => $b['min'], 'age_max' => $b['max']], $f->toQuery()),
            ];
        })->all();
    }

    /* ───────────────────────── Follow-up insights ───────────────────────── */

    public function followUpInsights(AnalyticsFilters $f): array
    {
        $now = Carbon::now();
        $open = config('analytics.lost_slugs', AnalyticsConfig::lostSlugs());
        $openOnly = fn (Builder $q) => $q->whereDoesntHave('status', fn ($s) => $s->whereIn('slug', $open));

        $metric = fn (callable $apply, array $query) => [
            'count' => (int) $apply($this->scoped()->whereNotNull('follow_up_at'))->count(),
            'query' => $query,
        ];

        return [
            'due_today' => $metric(
                fn ($q) => $q->whereBetween('follow_up_at', [$now->copy()->startOfDay(), $now->copy()->endOfDay()]),
                ['follow_up' => 'today']
            ),
            'overdue' => $metric(
                fn ($q) => $openOnly($q)->where('follow_up_at', '<', $now->copy()->startOfDay()),
                ['follow_up' => 'overdue']
            ),
            'upcoming' => $metric(
                fn ($q) => $q->where('follow_up_at', '>', $now->copy()->endOfDay()),
                ['follow_up' => 'upcoming']
            ),
            'missed' => $metric(
                fn ($q) => $openOnly($q)->whereBetween('follow_up_at', [$now->copy()->subDays(30), $now->copy()->startOfDay()]),
                ['follow_up' => 'missed']
            ),
            'completed' => [
                'count' => (int) LeadActivity::where('type', LeadActivity::TYPE_FOLLOWUP)
                    ->whereIn('lead_id', $this->scoped()->select('id'))->count(),
                'query' => ['follow_up' => 'completed'],
            ],
        ];
    }

    /* ───────────────────────── Trend (time series) ───────────────────────── */

    public function trend(AnalyticsFilters $f): array
    {
        $won = config('analytics.won_slugs', AnalyticsConfig::wonSlugs());
        $expr = $this->dateBucketExpr('created_at', $f->granularity);

        $created = $f->apply($this->scoped())
            ->selectRaw("$expr as bucket, COUNT(*) as c")
            ->groupBy('bucket')->orderBy('bucket')
            ->pluck('c', 'bucket');

        $converted = $this->withStatus($f->apply($this->scoped()), $won)
            ->selectRaw("$expr as bucket, COUNT(*) as c")
            ->groupBy('bucket')->orderBy('bucket')
            ->pluck('c', 'bucket');

        $buckets = $created->keys()->merge($converted->keys())->unique()->sort()->values();

        return [
            'granularity' => $f->granularity,
            'labels'      => $buckets->all(),
            'series'      => [
                ['key' => 'created',   'label' => 'Leads Created', 'data' => $buckets->map(fn ($b) => (int) ($created[$b] ?? 0))->all()],
                ['key' => 'converted', 'label' => 'Converted',     'data' => $buckets->map(fn ($b) => (int) ($converted[$b] ?? 0))->all()],
            ],
        ];
    }

    /* ───────────────────────── Conversion by dimension ───────────────────────── */

    public function conversion(AnalyticsFilters $f, string $by = 'source'): array
    {
        return match ($by) {
            'agent' => collect($this->agentPerformance($f, 8))
                ->map(fn ($a) => ['label' => $a['agent'], 'conversion' => $a['conversion'], 'total' => $a['assigned']])->values()->all(),
            'team'  => collect($this->teamPerformance($f))
                ->map(fn ($t) => ['label' => $t['team'], 'conversion' => $t['conversion'], 'total' => $t['volume']])->values()->all(),
            default => collect($this->sourcePerformance($f)['rows'])
                ->map(fn ($s) => ['label' => $s['source'], 'conversion' => $s['conversion'], 'total' => $s['total']])->values()->all(),
        };
    }

    /* ───────────────────────── Activity timeline ───────────────────────── */

    public function activityFeed(int $limit = 20): array
    {
        return LeadActivity::query()
            ->whereIn('lead_id', $this->scoped()->select('id'))
            ->with(['user:id,name', 'lead:id,customer_name'])
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn ($a) => [
                'id'       => $a->id,
                'type'     => $a->type,
                'title'    => $a->title,
                'body'     => $a->body,
                'user'     => optional($a->user)->name,
                'lead_id'  => $a->lead_id,
                'lead'     => optional($a->lead)->customer_name,
                'at'       => $a->created_at?->toIso8601String(),
            ])->all();
    }

    /* ───────────────────────── Executive (super_master / master) ───────────────────────── */

    public function executive(AnalyticsFilters $f): array
    {
        $now = Carbon::now();
        $won = config('analytics.won_slugs', AnalyticsConfig::wonSlugs());

        $totalLeads = $this->scoped()->count();
        $revenue = (float) $this->withStatus($this->scoped(), $won)->sum('budget');

        $thisMonth = $this->scoped()->whereBetween('created_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()])->count();
        $lastMonth = $this->scoped()->whereBetween('created_at', [$now->copy()->subMonth()->startOfMonth(), $now->copy()->subMonth()->endOfMonth()])->count();

        $userBase = User::query();
        if (! $this->user->isSuperMaster()) {
            $userBase->whereIn('id', $this->user->teamUserIds());
        }

        return [
            'total_clients'        => (clone $userBase)->where('role', User::ROLE_MASTER)->count(),
            'total_users'          => (clone $userBase)->count(),
            'total_leads'          => $totalLeads,
            'total_revenue'        => round($revenue, 2),
            'active_subscriptions' => (clone $userBase)->where('is_active', true)
                ->where(fn ($q) => $q->whereNull('subscription_expires_at')->orWhere('subscription_expires_at', '>=', $now))->count(),
            'expiring_accounts'    => (clone $userBase)->whereNotNull('subscription_expires_at')
                ->whereBetween('subscription_expires_at', [$now, $now->copy()->addDays(30)])->count(),
            'growth' => [
                'this_month' => $thisMonth,
                'last_month' => $lastMonth,
                'trend_pct'  => PeriodComparator::pct($thisMonth, $lastMonth),
            ],
        ];
    }

    /* ───────────────────────── Helpers ───────────────────────── */

    protected function withStatus(Builder $q, array $slugs): Builder
    {
        return $q->whereHas('status', fn ($s) => $s->whereIn('slug', $slugs));
    }

    /** Driver-aware date bucket expression (SQLite dev / MySQL prod). */
    protected function dateBucketExpr(string $column, string $granularity): string
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return match ($granularity) {
                'day'     => "strftime('%Y-%m-%d', $column)",
                'week'    => "strftime('%Y-W%W', $column)",
                'month'   => "strftime('%Y-%m', $column)",
                'quarter' => "strftime('%Y', $column) || '-Q' || ((cast(strftime('%m', $column) as integer) + 2) / 3)",
                'year'    => "strftime('%Y', $column)",
                default   => "strftime('%Y-%m-%d', $column)",
            };
        }

        // MySQL / MariaDB
        return match ($granularity) {
            'day'     => "DATE_FORMAT($column, '%Y-%m-%d')",
            'week'    => "DATE_FORMAT($column, '%x-W%v')",
            'month'   => "DATE_FORMAT($column, '%Y-%m')",
            'quarter' => "CONCAT(YEAR($column), '-Q', QUARTER($column))",
            'year'    => "DATE_FORMAT($column, '%Y')",
            default   => "DATE_FORMAT($column, '%Y-%m-%d')",
        };
    }
}
