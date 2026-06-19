<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'role')) {
            return;
        }

        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role ENUM('super_master','master','sub_master','agent','sub_agent') NOT NULL DEFAULT 'agent'");
        } elseif ($driver === 'pgsql') {
            DB::statement("ALTER TABLE users ALTER COLUMN role TYPE VARCHAR(32)");
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'role') && DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role ENUM('master','sub_master','agent') NOT NULL DEFAULT 'agent'");
        }
    }
};
