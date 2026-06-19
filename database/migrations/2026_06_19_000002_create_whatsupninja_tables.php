<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // One WhatsupNinja account connection per CRM tenant (account owner = root master).
        Schema::create('whatsupninja_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('base_url');
            $table->string('account_id')->nullable();
            // Credentials — encrypted at rest via the model's `encrypted` casts.
            $table->text('api_key');
            $table->text('username');
            $table->text('password');
            $table->text('jwt_token')->nullable();
            $table->timestamp('jwt_expires_at')->nullable();
            $table->string('status')->default('disconnected'); // connected | disconnected | error
            $table->text('last_error')->nullable();
            $table->string('webhook_token', 64)->unique();
            $table->string('webhook_secret', 64);
            $table->timestamp('last_synced_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('whatsapp_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('connection_id')->constrained('whatsupninja_connections')->cascadeOnDelete();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('remote_id')->nullable();          // WhatsupNinja template_id
            $table->string('name')->index();
            $table->string('category')->nullable();           // UTILITY | MARKETING | AUTHENTICATION
            $table->string('language', 16)->default('en');
            $table->string('status')->nullable();             // APPROVED | PENDING | REJECTED
            $table->json('components')->nullable();
            $table->json('variables')->nullable();            // parsed {{n}} body placeholders + count
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
            $table->unique(['connection_id', 'name', 'language']);
        });

        Schema::create('automation_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('trigger_event')->index();         // e.g. lead.status_changed
            $table->string('match_type')->default('all');     // all | any
            $table->unsignedInteger('delay_minutes')->default(0);
            $table->json('graph')->nullable();                // visual builder nodes/edges
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('executions_count')->default(0);
            $table->timestamp('last_run_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['owner_id', 'trigger_event', 'is_active']);
        });

        Schema::create('automation_rule_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rule_id')->constrained('automation_rules')->cascadeOnDelete();
            $table->string('field');                          // status_id, source_id, city, ...
            $table->string('operator');                       // equals, in, changed_to, ...
            $table->json('value')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('automation_rule_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rule_id')->constrained('automation_rules')->cascadeOnDelete();
            $table->string('type')->default('send_whatsapp_template');
            $table->foreignId('template_id')->nullable()->constrained('whatsapp_templates')->nullOnDelete();
            $table->json('config')->nullable();               // delay, variable_map, button values
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('whatsapp_message_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->foreignId('rule_id')->nullable()->constrained('automation_rules')->nullOnDelete();
            $table->foreignId('connection_id')->nullable()->constrained('whatsupninja_connections')->nullOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('whatsapp_templates')->nullOnDelete();
            $table->string('trigger_event')->nullable();
            $table->string('recipient')->nullable()->index();
            $table->json('variables')->nullable();
            $table->string('status')->default('queued');      // queued|sent|delivered|read|failed
            $table->string('remote_message_id')->nullable()->index();
            $table->text('error_message')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->json('response_payload')->nullable();
            $table->timestamps();
            $table->index(['owner_id', 'status']);
            $table->index(['lead_id', 'rule_id']);
        });

        Schema::create('whatsapp_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('connection_id')->nullable()->constrained('whatsupninja_connections')->nullOnDelete();
            $table->string('event_type')->nullable()->index();
            $table->string('remote_message_id')->nullable()->index();
            $table->json('payload')->nullable();
            $table->boolean('signature_valid')->default(false);
            $table->boolean('processed')->default(false);
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_webhook_events');
        Schema::dropIfExists('whatsapp_message_logs');
        Schema::dropIfExists('automation_rule_actions');
        Schema::dropIfExists('automation_rule_conditions');
        Schema::dropIfExists('automation_rules');
        Schema::dropIfExists('whatsapp_templates');
        Schema::dropIfExists('whatsupninja_connections');
    }
};
