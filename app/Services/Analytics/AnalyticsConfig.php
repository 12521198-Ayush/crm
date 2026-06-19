<?php

namespace App\Services\Analytics;

/**
 * Built-in analytics defaults.
 *
 * These live in code (not only config/analytics.php) so the analytics engine
 * keeps working even if `config:cache` was rebuilt before config/analytics.php
 * was deployed (a real risk with manual hPanel uploads). Call sites use
 * `config('analytics.x', AnalyticsConfig::x())` so config still wins when
 * present, but a missing/stale key falls back here instead of crashing.
 */
class AnalyticsConfig
{
    public static function funnel(): array
    {
        return [
            ['key' => 'new',        'label' => 'New',               'slugs' => ['untouched', 'new', 'fresh']],
            ['key' => 'contacted',  'label' => 'Contacted',         'slugs' => ['cold', 'callback', 'follow-up', 'pending']],
            ['key' => 'interested', 'label' => 'Interested',        'slugs' => ['interested']],
            ['key' => 'meeting',    'label' => 'Meeting Scheduled',  'slugs' => ['meeting-scheduled', 'meeting']],
            ['key' => 'converted',  'label' => 'Converted',          'slugs' => ['converted']],
        ];
    }

    public static function wonSlugs(): array
    {
        return ['converted'];
    }

    public static function lostSlugs(): array
    {
        return ['not-interested', 'dropped', 'closed'];
    }

    public static function cardGroups(): array
    {
        return [
            'new'            => ['new', 'fresh', 'untouched'],
            'pending'        => ['pending', 'follow-up'],
            'meeting'        => ['meeting-scheduled', 'meeting'],
            'not_interested' => ['not-interested'],
            'dropped'        => ['dropped', 'closed'],
        ];
    }

    public static function agingBuckets(): array
    {
        return [
            ['key' => '0-3',   'label' => '0–3 days',   'min' => 0,  'max' => 3],
            ['key' => '4-7',   'label' => '4–7 days',   'min' => 4,  'max' => 7],
            ['key' => '8-15',  'label' => '8–15 days',  'min' => 8,  'max' => 15],
            ['key' => '16-30', 'label' => '16–30 days', 'min' => 16, 'max' => 30],
            ['key' => '30+',   'label' => '30+ days',   'min' => 31, 'max' => null],
        ];
    }
}
