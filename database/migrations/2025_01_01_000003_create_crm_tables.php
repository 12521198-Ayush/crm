<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable()->unique();
            $table->text('description')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('lead_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('channel')->nullable(); // ivr, meta_fb, meta_ig, google_ads, manual, web_form
            $table->json('config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('lead_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->string('color', 16)->default('#64748b');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_system')->default(false);
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['slug', 'owner_id']);
        });

        Schema::create('custom_fields', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->string('key')->unique();
            $table->string('type')->default('text'); // text, number, select, date, textarea
            $table->json('options')->nullable();
            $table->boolean('required')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name');
            $table->string('mobile')->nullable()->index();
            $table->string('email')->nullable()->index();
            $table->string('city')->nullable();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignId('source_id')->nullable()->constrained('lead_sources')->nullOnDelete();
            $table->string('sub_source')->nullable()->index();
            $table->foreignId('status_id')->nullable()->constrained('lead_statuses')->nullOnDelete();
            $table->decimal('budget', 14, 2)->nullable();
            $table->text('remarks')->nullable();
            $table->timestamp('follow_up_at')->nullable()->index();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('external_id')->nullable()->index();
            $table->string('campaign_name')->nullable();
            $table->string('ad_set_name')->nullable();
            $table->string('ad_name')->nullable();
            $table->string('form_name')->nullable();
            $table->json('custom_fields')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();
            $table->index(['status_id', 'assigned_to']);
        });

        Schema::create('lead_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type')->default('note');
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index(['lead_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_activities');
        Schema::dropIfExists('leads');
        Schema::dropIfExists('custom_fields');
        Schema::dropIfExists('lead_statuses');
        Schema::dropIfExists('lead_sources');
        Schema::dropIfExists('projects');
    }
};
