<?php

namespace Database\Seeders;

use App\Models\LeadSource;
use App\Models\LeadStatus;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Master
        $master = User::firstOrCreate(
            ['email' => 'master@ninja.test'],
            ['name' => 'Master Admin', 'password' => Hash::make('password'), 'role' => User::ROLE_MASTER, 'is_active' => true]
        );

        // Sub master under master
        $sub = User::firstOrCreate(
            ['email' => 'submaster@ninja.test'],
            ['name' => 'Sub Master', 'password' => Hash::make('password'), 'role' => User::ROLE_SUB_MASTER, 'parent_id' => $master->id, 'is_active' => true]
        );

        // Agent under sub master
        User::firstOrCreate(
            ['email' => 'agent@ninja.test'],
            ['name' => 'Agent One', 'password' => Hash::make('password'), 'role' => User::ROLE_AGENT, 'parent_id' => $sub->id, 'is_active' => true]
        );

        // System statuses
        $defaults = [
            ['name' => 'Untouched',      'color' => '#94a3b8', 'sort_order' => 1],
            ['name' => 'New',            'color' => '#3b82f6', 'sort_order' => 2],
            ['name' => 'Fresh',          'color' => '#2563eb', 'sort_order' => 3],
            ['name' => 'Interested',     'color' => '#10b981', 'sort_order' => 4],
            ['name' => 'Pending',        'color' => '#f59e0b', 'sort_order' => 5],
            ['name' => 'Follow-up',      'color' => '#d97706', 'sort_order' => 6],
            ['name' => 'Callback',       'color' => '#6366f1', 'sort_order' => 7],
            ['name' => 'Meeting Scheduled', 'color' => '#0891b2', 'sort_order' => 8],
            ['name' => 'Converted',      'color' => '#059669', 'sort_order' => 9],
            ['name' => 'Cold',           'color' => '#0ea5e9', 'sort_order' => 10],
            ['name' => 'Not Interested', 'color' => '#ef4444', 'sort_order' => 11],
            ['name' => 'Dropped',        'color' => '#64748b', 'sort_order' => 12],
            ['name' => 'Closed',         'color' => '#6b7280', 'sort_order' => 13],
        ];
        foreach ($defaults as $s) {
            LeadStatus::firstOrCreate(
                ['slug' => Str::slug($s['name']), 'owner_id' => null],
                array_merge($s, ['slug' => Str::slug($s['name']), 'is_system' => true])
            );
        }

        // Sources
        $sources = [
            ['name' => 'IVR / Voice Call', 'slug' => 'ivr',         'channel' => 'ivr'],
            ['name' => 'Facebook Lead Ad', 'slug' => 'meta_fb',     'channel' => 'meta_fb'],
            ['name' => 'Instagram Lead Ad','slug' => 'meta_ig',     'channel' => 'meta_ig'],
            ['name' => 'Google Ads',       'slug' => 'google_ads',  'channel' => 'google_ads'],
            ['name' => 'Manual Entry',     'slug' => 'manual',      'channel' => 'manual'],
            ['name' => 'Web Form',         'slug' => 'web_form',    'channel' => 'web_form'],
        ];
        foreach ($sources as $src) {
            LeadSource::firstOrCreate(['slug' => $src['slug']], $src + ['is_active' => true]);
        }

        // Demo project
        Project::firstOrCreate(
            ['name' => 'Skyline Residency'],
            ['code' => 'SKY01', 'description' => 'Demo project', 'owner_id' => $master->id, 'is_active' => true]
        );
    }
}
