<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\LeadIngestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaWebhookController extends Controller
{
    public function handle(Request $request, LeadIngestService $ingest)
    {
        // Verification challenge (GET)
        if ($request->isMethod('get')) {
            $mode    = $request->query('hub_mode');
            $token   = $request->query('hub_verify_token');
            $chal    = $request->query('hub_challenge');
            if ($mode === 'subscribe' && $token === config('services.meta.verify_token')) {
                return response($chal, 200);
            }
            return response('Forbidden', 403);
        }

        $payload = $request->all();
        Log::info('meta.webhook', $payload);

        // Facebook Lead Ads webhook structure: entry[].changes[].value.leadgen_id
        foreach (data_get($payload, 'entry', []) as $entry) {
            foreach (data_get($entry, 'changes', []) as $change) {
                $value = $change['value'] ?? [];
                $leadgenId = $value['leadgen_id'] ?? null;
                $form = $value['form_id'] ?? null;
                $channel = ($value['ad_id'] ?? null) ? 'meta_fb' : 'meta_fb';

                $fields = $this->fetchLeadFields($leadgenId);

                $ingest->ingest($channel, array_merge($fields, [
                    'form_id' => $form,
                    'form_name' => $fields['form_name'] ?? $form,
                    'campaign_name' => $fields['campaign_name'] ?? ($value['campaign_name'] ?? null),
                    'ad_set_name' => $fields['ad_set_name'] ?? ($value['adset_name'] ?? $value['ad_set_name'] ?? null),
                    'ad_name' => $fields['ad_name'] ?? ($value['ad_name'] ?? null),
                    'sub_source' => $fields['ad_set_name'] ?? ($value['adset_name'] ?? $value['ad_set_name'] ?? null),
                    'leadgen_id' => $leadgenId,
                ]), $leadgenId);
            }
        }

        return response()->json(['ok' => true]);
    }

    private function fetchLeadFields(?string $leadgenId): array
    {
        if (! $leadgenId) return [];
        $token = config('services.meta.page_access_token');
        if (! $token) return [];

        try {
            $resp = Http::get("https://graph.facebook.com/v19.0/{$leadgenId}", [
                'access_token' => $token,
                'fields' => 'field_data,created_time,ad_id,ad_name,adset_name,campaign_name,form_id',
            ])->json();

            $fields = [];
            foreach (data_get($resp, 'field_data', []) as $f) {
                $name = strtolower($f['name'] ?? '');
                $val  = $f['values'][0] ?? null;
                $map = [
                    'full_name' => 'customer_name', 'name' => 'customer_name',
                    'phone_number' => 'mobile', 'phone' => 'mobile',
                    'email' => 'email', 'city' => 'city',
                ];
                $key = $map[$name] ?? $name;
                $fields[$key] = $val;
            }
            $fields['campaign_name'] = $resp['campaign_name'] ?? null;
            $fields['ad_set_name'] = $resp['adset_name'] ?? null;
            $fields['ad_name'] = $resp['ad_name'] ?? null;
            $fields['form_name'] = $resp['form_id'] ?? null;
            return $fields;
        } catch (\Throwable $e) {
            Log::warning('meta.lead.fetch.failed', ['err' => $e->getMessage()]);
            return [];
        }
    }
}
