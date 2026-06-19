<?php

namespace App\Services\Analytics;

use Closure;

/**
 * Trend engine. Given a query factory (so the base scope is rebuilt each call)
 * it counts the current vs previous window and returns a signed % change.
 */
class PeriodComparator
{
    /**
     * @param  Closure  $queryFactory  fn(): Builder — fresh scoped Lead query
     */
    public static function compare(Closure $queryFactory, AnalyticsFilters $filters): array
    {
        $current = (int) $filters->apply($queryFactory())->count();

        $previous = null;
        $prev = $filters->previousRange();
        if ($prev) {
            $previous = (int) $queryFactory()
                ->whereBetween($filters->dateColumn, [$prev[0], $prev[1]])
                ->count();
        }

        return [
            'value'     => $current,
            'previous'  => $previous,
            'trend_pct' => self::pct($current, $previous),
            'direction' => self::direction($current, $previous),
        ];
    }

    public static function pct(int $current, ?int $previous): ?float
    {
        if ($previous === null) return null;
        if ($previous === 0) return $current > 0 ? 100.0 : 0.0;
        return round((($current - $previous) / $previous) * 100, 1);
    }

    public static function direction(int $current, ?int $previous): string
    {
        if ($previous === null || $current === $previous) return 'flat';
        return $current > $previous ? 'up' : 'down';
    }
}
