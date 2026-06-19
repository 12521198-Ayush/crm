<?php

namespace App\Services\Analytics;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Immutable-ish value object describing the active analytics filter context.
 *
 * Reused by every analytics endpoint (dashboard today, lead/agent/team/exec
 * reports tomorrow). Knows how to (a) apply dimension + date filters to any
 * Lead query and (b) describe the comparison ("previous") window for trends.
 */
class AnalyticsFilters
{
    public function __construct(
        public string $period = 'month',      // today | week | month | quarter | year | all | custom
        public string $granularity = 'day',   // day | week | month | quarter | year
        public ?Carbon $from = null,
        public ?Carbon $to = null,
        public ?int $projectId = null,
        public ?int $sourceId = null,
        public ?int $statusId = null,
        public ?int $assignedTo = null,
        public string $dateColumn = 'created_at',
    ) {}

    public static function fromRequest(Request $request): self
    {
        $period = $request->input('period', 'month');
        [$from, $to] = self::resolveRange(
            $period,
            $request->input('from'),
            $request->input('to'),
        );

        return new self(
            period: $period,
            granularity: $request->input('granularity', self::defaultGranularity($period)),
            from: $from,
            to: $to,
            projectId: $request->integer('project_id') ?: null,
            sourceId: $request->integer('source_id') ?: null,
            statusId: $request->integer('status_id') ?: null,
            assignedTo: $request->integer('assigned_to') ?: null,
        );
    }

    /** Resolve a [from, to] Carbon window for the named period. */
    protected static function resolveRange(string $period, $from, $to): array
    {
        $now = Carbon::now();
        return match ($period) {
            'today'   => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'week'    => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'month'   => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'quarter' => [$now->copy()->startOfQuarter(), $now->copy()->endOfQuarter()],
            'year'    => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            'custom'  => [
                $from ? Carbon::parse($from)->startOfDay() : $now->copy()->subDays(30)->startOfDay(),
                $to ? Carbon::parse($to)->endOfDay() : $now->copy()->endOfDay(),
            ],
            default   => [null, null], // 'all'
        };
    }

    protected static function defaultGranularity(string $period): string
    {
        return match ($period) {
            'today', 'week' => 'day',
            'month'         => 'day',
            'quarter'       => 'week',
            'year', 'all'   => 'month',
            default         => 'day',
        };
    }

    /** Apply dimension filters (and optionally the date window) to a query. */
    public function apply(Builder $query, bool $withDate = true): Builder
    {
        if ($this->projectId)  $query->where('project_id', $this->projectId);
        if ($this->sourceId)   $query->where('source_id', $this->sourceId);
        if ($this->statusId)   $query->where('status_id', $this->statusId);
        if ($this->assignedTo) $query->where('assigned_to', $this->assignedTo);

        if ($withDate && $this->from && $this->to) {
            $query->whereBetween($this->dateColumn, [$this->from, $this->to]);
        }
        return $query;
    }

    /** The equal-length window immediately preceding the current one. */
    public function previousRange(): ?array
    {
        if (! $this->from || ! $this->to) return null;
        $length = $this->from->diffInSeconds($this->to);
        $prevTo = $this->from->copy()->subSecond();
        $prevFrom = $prevTo->copy()->subSeconds($length);
        return [$prevFrom, $prevTo];
    }

    public function comparisonLabel(): string
    {
        return match ($this->period) {
            'today'   => 'yesterday',
            'week'    => 'last week',
            'month'   => 'last month',
            'quarter' => 'last quarter',
            'year'    => 'last year',
            default   => 'previous period',
        };
    }

    /** Filters serialized as query params for front-end drill-down links. */
    public function toQuery(): array
    {
        return array_filter([
            'project_id'  => $this->projectId,
            'source_id'   => $this->sourceId,
            'status_id'   => $this->statusId,
            'assigned_to' => $this->assignedTo,
        ]);
    }
}
