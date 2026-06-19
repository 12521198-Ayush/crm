<?php

use App\Services\Analytics\AnalyticsConfig;

/*
|--------------------------------------------------------------------------
| Analytics configuration
|--------------------------------------------------------------------------
| Central, reusable definition of the funnel, status groupings and aging
| buckets. Consumed by the analytics services so the dashboard, reports and
| (future) custom dashboards all share one source of truth.
|
| Values are sourced from App\Services\Analytics\AnalyticsConfig so the same
| defaults exist in code — the analytics engine keeps working even if this
| config file is missing from a (stale) config cache. Override here per tenant
| later if needed.
*/

return [
    'funnel'        => AnalyticsConfig::funnel(),
    'won_slugs'     => AnalyticsConfig::wonSlugs(),
    'lost_slugs'    => AnalyticsConfig::lostSlugs(),
    'card_groups'   => AnalyticsConfig::cardGroups(),
    'aging_buckets' => AnalyticsConfig::agingBuckets(),
];
