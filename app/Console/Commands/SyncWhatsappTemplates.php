<?php

namespace App\Console\Commands;

use App\Models\WhatsupninjaConnection;
use App\Services\WhatsupNinja\TemplateSyncService;
use Illuminate\Console\Command;
use Throwable;

class SyncWhatsappTemplates extends Command
{
    protected $signature = 'whatsupninja:sync-templates';

    protected $description = 'Sync WhatsApp templates for every connected WhatsupNinja account';

    public function handle(TemplateSyncService $sync): int
    {
        $connections = WhatsupninjaConnection::where('is_active', true)
            ->where('status', 'connected')
            ->get();

        foreach ($connections as $connection) {
            try {
                $count = $sync->sync($connection);
                $this->info("Owner {$connection->owner_id}: synced {$count} template(s).");
            } catch (Throwable $e) {
                $connection->forceFill(['status' => 'error', 'last_error' => $e->getMessage()])->save();
                $this->error("Owner {$connection->owner_id}: sync failed - {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
