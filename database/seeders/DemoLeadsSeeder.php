<?php

namespace Database\Seeders;

use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadSource;
use App\Models\LeadStatus;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

/**
 * Generates a realistic, time-distributed lead dataset so the analytics
 * dashboard (trends, funnel, aging, leaderboard, follow-ups) renders with
 * meaningful data. Idempotent-ish: only seeds when few leads exist.
 *
 *   php artisan db:seed --class=Database\\Seeders\\DemoLeadsSeeder
 */
class DemoLeadsSeeder extends Seeder
{
    public function run(): void
    {
        if (Lead::count() > 50) {
            $this->command?->warn('Leads already present — skipping demo seed.');
            return;
        }

        $master = User::where('email', 'master@ninja.test')->first();
        $sub = User::where('email', 'submaster@ninja.test')->first();
        if (! $master || ! $sub) {
            $this->command?->error('Run the base DatabaseSeeder first.');
            return;
        }

        // A small team of agents under the sub-master for leaderboard variety.
        $agents = collect(['Aarav Sharma', 'Priya Patel', 'Rohan Mehta', 'Sneha Iyer', 'Vikram Singh'])
            ->map(fn ($name, $i) => User::firstOrCreate(
                ['email' => 'agent' . ($i + 1) . '@ninja.test'],
                ['name' => $name, 'password' => Hash::make('password'), 'role' => User::ROLE_AGENT, 'parent_id' => $sub->id, 'is_active' => true]
            ));
        // Include the originally-seeded agent too.
        if ($base = User::where('email', 'agent@ninja.test')->first()) $agents->push($base);

        // A second team for team-performance comparison.
        $sub2 = User::firstOrCreate(
            ['email' => 'submaster2@ninja.test'],
            ['name' => 'Neha Gupta', 'password' => Hash::make('password'), 'role' => User::ROLE_SUB_MASTER, 'parent_id' => $master->id, 'is_active' => true]
        );
        $team2 = collect(['Karan Joshi', 'Divya Rao'])
            ->map(fn ($name, $i) => User::firstOrCreate(
                ['email' => 'agentb' . ($i + 1) . '@ninja.test'],
                ['name' => $name, 'password' => Hash::make('password'), 'role' => User::ROLE_AGENT, 'parent_id' => $sub2->id, 'is_active' => true]
            ));

        $allAgents = $agents->merge($team2)->values();

        $statuses = LeadStatus::whereNull('owner_id')->get()->keyBy('slug');
        $sources = LeadSource::all();
        $projects = Project::all();
        if ($projects->isEmpty()) {
            $projects = collect([Project::create(['name' => 'Skyline Residency', 'code' => 'SKY01', 'owner_id' => $master->id, 'is_active' => true])]);
        }

        // Status distribution skewed toward a realistic funnel.
        $statusPool = array_merge(
            array_fill(0, 22, 'untouched'),
            array_fill(0, 14, 'new'),
            array_fill(0, 8,  'fresh'),
            array_fill(0, 12, 'cold'),
            array_fill(0, 10, 'follow-up'),
            array_fill(0, 8,  'pending'),
            array_fill(0, 6,  'callback'),
            array_fill(0, 10, 'interested'),
            array_fill(0, 7,  'meeting-scheduled'),
            array_fill(0, 6,  'converted'),
            array_fill(0, 9,  'not-interested'),
            array_fill(0, 5,  'dropped'),
        );

        $subSources = ['Summer Campaign', 'Retargeting', 'Lookalike', 'Brand Search', 'Local SEO', null];
        $campaigns = ['Q2-Growth', 'Festive-Push', 'Always-On', 'New-Launch'];
        $cities = ['Mumbai', 'Pune', 'Bengaluru', 'Delhi', 'Hyderabad', 'Chennai', 'Ahmedabad'];

        $total = 320;
        $this->command?->getOutput()->progressStart($total);

        for ($i = 0; $i < $total; $i++) {
            // Bias creation dates toward recent days (more leads lately = growth).
            $daysAgo = (int) floor(pow(mt_rand(0, 100) / 100, 1.6) * 120);
            $createdAt = Carbon::now()->subDays($daysAgo)->subMinutes(mt_rand(0, 1440));

            $slug = $statusPool[array_rand($statusPool)];
            $status = $statuses->get($slug) ?? $statuses->first();
            $agent = $allAgents->random();
            $source = $sources->random();
            $isClosed = in_array($slug, ['converted', 'not-interested', 'dropped', 'closed']);

            // Follow-up timing: spread across overdue / today / upcoming for open leads.
            $followUp = null;
            if (! $isClosed && mt_rand(0, 100) < 65) {
                $offset = mt_rand(-12, 14); // days
                $followUp = Carbon::now()->addDays($offset)->setTime(mt_rand(9, 18), [0, 30][array_rand([0, 30])]);
            }

            $lead = Lead::create([
                'customer_name' => $this->name(),
                'mobile'        => '9' . mt_rand(100000000, 999999999),
                'email'         => 'lead' . $i . '@example.com',
                'city'          => $cities[array_rand($cities)],
                'project_id'    => $projects->random()->id,
                'source_id'     => $source->id,
                'sub_source'    => $subSources[array_rand($subSources)],
                'status_id'     => $status?->id,
                'budget'        => [null, 250000, 500000, 1000000, 2500000, 5000000][array_rand([0,1,2,3,4,5])],
                'follow_up_at'  => $followUp,
                'assigned_to'   => mt_rand(0, 100) < 88 ? $agent->id : null,
                'created_by'    => $sub->id,
                'campaign_name' => $campaigns[array_rand($campaigns)],
                'created_at'    => $createdAt,
                'updated_at'    => $createdAt,
            ]);

            $this->seedActivities($lead, $agent, $slug, $createdAt);
            $this->command?->getOutput()->progressAdvance();
        }

        $this->command?->getOutput()->progressFinish();
        $this->command?->info("Seeded {$total} demo leads with activities.");
    }

    private function seedActivities(Lead $lead, User $agent, string $slug, Carbon $createdAt): void
    {
        // First contact shortly after creation (drives response-time metric).
        $contactGap = mt_rand(10, 2880); // minutes
        $firstContact = (clone $createdAt)->addMinutes($contactGap);
        LeadActivity::create([
            'lead_id' => $lead->id, 'user_id' => $agent->id,
            'type' => [LeadActivity::TYPE_CALL, LeadActivity::TYPE_WHATSAPP][array_rand([0, 1])],
            'title' => 'First contact attempt', 'created_at' => $firstContact, 'updated_at' => $firstContact,
        ]);

        // Follow-up activities for some leads (drives "completed" follow-ups).
        if (in_array($slug, ['follow-up', 'pending', 'callback', 'interested', 'meeting-scheduled', 'converted']) && mt_rand(0, 100) < 70) {
            $at = (clone $firstContact)->addHours(mt_rand(2, 72));
            LeadActivity::create([
                'lead_id' => $lead->id, 'user_id' => $agent->id,
                'type' => LeadActivity::TYPE_FOLLOWUP, 'title' => 'Follow-up done',
                'created_at' => $at, 'updated_at' => $at,
            ]);
        }

        if (in_array($slug, ['meeting-scheduled', 'converted'])) {
            $at = (clone $firstContact)->addDays(mt_rand(1, 5));
            LeadActivity::create([
                'lead_id' => $lead->id, 'user_id' => $agent->id,
                'type' => LeadActivity::TYPE_MEETING, 'title' => 'Meeting scheduled',
                'created_at' => $at, 'updated_at' => $at,
            ]);
        }

        // Status change record for the current status.
        LeadActivity::create([
            'lead_id' => $lead->id, 'user_id' => $agent->id,
            'type' => LeadActivity::TYPE_STATUS, 'title' => 'Status updated to ' . $slug,
            'meta' => ['to_slug' => $slug],
            'created_at' => (clone $firstContact)->addHours(1), 'updated_at' => (clone $firstContact)->addHours(1),
        ]);
    }

    private function name(): string
    {
        $first = ['Amit', 'Ravi', 'Pooja', 'Anjali', 'Suresh', 'Meera', 'Arjun', 'Kavya', 'Sanjay', 'Deepa', 'Manish', 'Nisha'];
        $last = ['Verma', 'Reddy', 'Nair', 'Kapoor', 'Bose', 'Malhotra', 'Chopra', 'Desai', 'Pillai', 'Shah'];
        return $first[array_rand($first)] . ' ' . $last[array_rand($last)];
    }
}
