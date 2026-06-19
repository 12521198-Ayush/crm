<?php

namespace App\Services\Analytics;

use App\Services\Analytics\AnalyticsConfig;
use Closure;

/**
 * Builds the lead funnel with stage-wise conversion and drop-off.
 *
 * With only the lead's *current* status available we model "reached" counts
 * cumulatively: a lead sitting at stage N is assumed to have passed through
 * stages 1..N. This yields a monotonic funnel and meaningful conversion %.
 */
class FunnelBuilder
{
    /**
     * @param  Closure  $queryFactory  fn(): Builder — fresh scoped+filtered Lead query
     */
    public static function build(Closure $queryFactory): array
    {
        $stages = config('analytics.funnel', AnalyticsConfig::funnel());

        // Current-status count per stage.
        $stageCounts = [];
        foreach ($stages as $i => $stage) {
            $stageCounts[$i] = (int) $queryFactory()
                ->whereHas('status', fn ($q) => $q->whereIn('slug', $stage['slugs']))
                ->count();
        }

        // Cumulative "reached" = this stage + everything after it.
        $total = array_sum($stageCounts);
        $rows = [];
        $entered = null;
        foreach ($stages as $i => $stage) {
            $reached = 0;
            for ($j = $i; $j < count($stages); $j++) $reached += $stageCounts[$j];
            if ($entered === null) $entered = $reached; // funnel entry = first stage reached

            $prevReached = $i === 0 ? $reached : $rows[$i - 1]['reached'];
            $convFromPrev = $prevReached > 0 ? round(($reached / $prevReached) * 100, 1) : 0.0;

            $rows[] = [
                'key'          => $stage['key'],
                'label'        => $stage['label'],
                'slugs'        => $stage['slugs'],
                'at_stage'     => $stageCounts[$i],
                'reached'      => $reached,
                'pct_of_total' => $entered > 0 ? round(($reached / $entered) * 100, 1) : 0.0,
                'conversion'   => $i === 0 ? 100.0 : $convFromPrev,       // vs previous stage
                'drop_off'     => $i === 0 ? 0.0 : round(100 - $convFromPrev, 1),
            ];
        }

        $convertedReached = end($rows)['reached'] ?? 0;
        return [
            'stages'           => $rows,
            'entered'          => $entered ?? 0,
            'converted'        => $convertedReached,
            'overall_conversion' => ($entered ?? 0) > 0 ? round(($convertedReached / $entered) * 100, 1) : 0.0,
        ];
    }
}
