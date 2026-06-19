<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Analytics\AnalyticsFilters;
use App\Services\Analytics\AnalyticsService;
use Illuminate\Http\Request;

/**
 * Thin HTTP layer over AnalyticsService. Granular endpoints exist so future
 * report screens (lead/agent/team/executive/custom dashboards/exports) can
 * reuse exactly the same metrics the dashboard renders.
 */
class AnalyticsController extends Controller
{
    protected function service(Request $request): AnalyticsService
    {
        return new AnalyticsService($request->user());
    }

    protected function filters(Request $request): AnalyticsFilters
    {
        return AnalyticsFilters::fromRequest($request);
    }

    public function overview(Request $request)
    {
        return ['cards' => $this->service($request)->summaryCards($this->filters($request))];
    }

    public function funnel(Request $request)
    {
        return $this->service($request)->funnel($this->filters($request));
    }

    public function sources(Request $request)
    {
        return $this->service($request)->sourcePerformance($this->filters($request));
    }

    public function agents(Request $request)
    {
        return ['rows' => $this->service($request)->agentPerformance($this->filters($request), (int) $request->input('limit', 10))];
    }

    public function teams(Request $request)
    {
        return ['rows' => $this->service($request)->teamPerformance($this->filters($request))];
    }

    public function aging(Request $request)
    {
        return ['buckets' => $this->service($request)->leadAging($this->filters($request))];
    }

    public function followUps(Request $request)
    {
        return $this->service($request)->followUpInsights($this->filters($request));
    }

    public function trend(Request $request)
    {
        return $this->service($request)->trend($this->filters($request));
    }

    public function conversion(Request $request)
    {
        $by = $request->input('by', 'source');
        return ['by' => $by, 'rows' => $this->service($request)->conversion($this->filters($request), $by)];
    }

    public function activity(Request $request)
    {
        return ['rows' => $this->service($request)->activityFeed((int) $request->input('limit', 20))];
    }

    public function executive(Request $request)
    {
        $user = $request->user();
        abort_unless($user->isMaster() || $user->isSuperMaster(), 403);
        return $this->service($request)->executive($this->filters($request));
    }

    /** Single aggregate call used by the dashboard to avoid N round-trips. */
    public function dashboard(Request $request)
    {
        $svc = $this->service($request);
        $f = $this->filters($request);
        $user = $request->user();

        $payload = [
            'filters' => [
                'period'      => $f->period,
                'granularity' => $f->granularity,
                'compared_to' => $f->comparisonLabel(),
            ],
            'cards'       => $svc->summaryCards($f),
            'funnel'      => $svc->funnel($f),
            'sources'     => $svc->sourcePerformance($f),
            'agents'      => $svc->agentPerformance($f, 8),
            'teams'       => $svc->teamPerformance($f),
            'aging'       => $svc->leadAging($f),
            'follow_ups'  => $svc->followUpInsights($f),
            'trend'       => $svc->trend($f),
            'activity'    => $svc->activityFeed(15),
        ];

        if ($user->isMaster() || $user->isSuperMaster()) {
            $payload['executive'] = $svc->executive($f);
        }

        return $payload;
    }
}
