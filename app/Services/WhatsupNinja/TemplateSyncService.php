<?php

namespace App\Services\WhatsupNinja;

use App\Models\WhatsappTemplate;
use App\Models\WhatsupninjaConnection;

class TemplateSyncService
{
    /**
     * Pull templates from WhatsupNinja and upsert them. Templates missing from
     * the remote response are marked inactive. Returns the synced count.
     */
    public function sync(WhatsupninjaConnection $connection): int
    {
        $remote = (new WhatsupNinjaClient($connection))->templates();
        $seenIds = [];

        foreach ($remote as $t) {
            $name = $t['name'] ?? null;
            if (! $name) {
                continue;
            }
            $language = $t['language'] ?? 'en';
            $components = $t['components'] ?? [];

            $template = WhatsappTemplate::updateOrCreate(
                [
                    'connection_id' => $connection->id,
                    'name'          => $name,
                    'language'      => $language,
                ],
                [
                    'owner_id'       => $connection->owner_id,
                    'remote_id'      => $t['template_id'] ?? ($t['id'] ?? null),
                    'category'       => $t['category'] ?? null,
                    'status'         => $t['status'] ?? null,
                    'components'     => $components,
                    'variables'      => $this->parseVariables($components),
                    'is_active'      => true,
                    'last_synced_at' => now(),
                ]
            );
            $seenIds[] = $template->id;
        }

        // Soft-disable templates that no longer exist remotely.
        WhatsappTemplate::where('connection_id', $connection->id)
            ->when($seenIds, fn ($q) => $q->whereNotIn('id', $seenIds))
            ->update(['is_active' => false]);

        $connection->forceFill(['last_synced_at' => now()])->save();

        return count($seenIds);
    }

    /**
     * Extract {{1}}..{{n}} placeholders from the BODY (and BUTTON URL) components.
     * Returns: ['body_count' => n, 'body' => [1,2,...], 'buttons' => [...]].
     */
    public function parseVariables(array $components): array
    {
        $bodyText = '';
        $buttonTokens = [];

        foreach ($components as $c) {
            $type = strtoupper($c['type'] ?? '');
            if ($type === 'BODY') {
                $bodyText = $c['text'] ?? '';
            }
            if ($type === 'BUTTONS') {
                foreach ($c['buttons'] ?? [] as $b) {
                    if (! empty($b['url']) && preg_match_all('/\{\{(\d+)\}\}/', $b['url'], $m)) {
                        $buttonTokens = array_merge($buttonTokens, $m[1]);
                    }
                }
            }
        }

        preg_match_all('/\{\{(\d+)\}\}/', $bodyText, $bodyMatches);
        $bodyTokens = array_values(array_unique(array_map('intval', $bodyMatches[1])));
        sort($bodyTokens);

        return [
            'body_count' => count($bodyTokens),
            'body'       => $bodyTokens,
            'buttons'    => array_values(array_unique(array_map('intval', $buttonTokens))),
        ];
    }
}
