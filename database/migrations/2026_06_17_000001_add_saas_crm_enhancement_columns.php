<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (! Schema::hasColumn('leads', 'sub_source')) {
                $table->string('sub_source')->nullable()->after('source_id')->index();
            }
            if (! Schema::hasColumn('leads', 'campaign_name')) {
                $table->string('campaign_name')->nullable()->after('external_id');
            }
            if (! Schema::hasColumn('leads', 'ad_set_name')) {
                $table->string('ad_set_name')->nullable()->after('campaign_name');
            }
            if (! Schema::hasColumn('leads', 'ad_name')) {
                $table->string('ad_name')->nullable()->after('ad_set_name');
            }
            if (! Schema::hasColumn('leads', 'form_name')) {
                $table->string('form_name')->nullable()->after('ad_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            foreach (['form_name', 'ad_name', 'ad_set_name', 'campaign_name', 'sub_source'] as $column) {
                if (Schema::hasColumn('leads', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
