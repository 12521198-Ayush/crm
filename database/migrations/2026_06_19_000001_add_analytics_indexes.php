<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indexes to keep analytics aggregations fast at scale (100k+ leads):
 * trend buckets / period comparisons hit created_at heavily, and
 * source/assignee group-bys over a date window benefit from composite indexes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->index('created_at', 'leads_created_at_idx');
            $table->index(['source_id', 'created_at'], 'leads_source_created_idx');
            $table->index(['assigned_to', 'created_at'], 'leads_assigned_created_idx');
        });

        Schema::table('lead_activities', function (Blueprint $table) {
            $table->index(['type', 'lead_id'], 'lead_activities_type_lead_idx');
            $table->index(['user_id', 'type'], 'lead_activities_user_type_idx');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex('leads_created_at_idx');
            $table->dropIndex('leads_source_created_idx');
            $table->dropIndex('leads_assigned_created_idx');
        });

        Schema::table('lead_activities', function (Blueprint $table) {
            $table->dropIndex('lead_activities_type_lead_idx');
            $table->dropIndex('lead_activities_user_type_idx');
        });
    }
};
