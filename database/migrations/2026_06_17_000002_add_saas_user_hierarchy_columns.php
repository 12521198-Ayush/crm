<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'subscription_plan')) {
                $table->string('subscription_plan')->nullable()->after('parent_id');
            }
            if (! Schema::hasColumn('users', 'subscription_starts_at')) {
                $table->date('subscription_starts_at')->nullable()->after('subscription_plan');
            }
            if (! Schema::hasColumn('users', 'subscription_expires_at')) {
                $table->date('subscription_expires_at')->nullable()->after('subscription_starts_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['subscription_expires_at', 'subscription_starts_at', 'subscription_plan'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
