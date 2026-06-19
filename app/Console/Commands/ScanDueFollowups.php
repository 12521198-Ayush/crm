<?php

namespace App\Console\Commands;

use App\Events\FollowUpDue;
use App\Models\Lead;
use Illuminate\Console\Command;

class ScanDueFollowups extends Command
{
    protected $signature = 'whatsupninja:scan-followups {--window=15 : Minutes back to scan for newly-due follow-ups}';

    protected $description = 'Fire FollowUpDue automation events for leads whose follow-up just became due';

    public function handle(): int
    {
        $window = (int) $this->option('window');
        $now = now();

        // Open statuses only (skip won/lost). Reuse analytics config like LeadController does.
        $closed = array_merge(config('analytics.lost_slugs', []), config('analytics.won_slugs', []));

        $fired = 0;

        // Just-became-due follow-ups (within the scan window).
        Lead::query()
            ->whereNotNull('mobile')
            ->whereNotNull('follow_up_at')
            ->whereBetween('follow_up_at', [$now->copy()->subMinutes($window), $now])
            ->when($closed, fn ($q) => $q->whereDoesntHave('status', fn ($s) => $s->whereIn('slug', $closed)))
            ->with(['assignee', 'creator'])
            ->chunkById(200, function ($leads) use (&$fired) {
                foreach ($leads as $lead) {
                    FollowUpDue::dispatch($lead, 'followup.due');
                    // Also surface as a meeting reminder so meeting.due rules can match.
                    FollowUpDue::dispatch($lead, 'meeting.due');
                    $fired++;
                }
            });

        $this->info("Scanned due follow-ups; fired events for {$fired} lead(s).");

        return self::SUCCESS;
    }
}
