<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\LeadIngestService;
use Illuminate\Http\Request;

class GoogleAdsWebhookController extends Controller
{
    public function handle(Request $request, LeadIngestService $ingest)
    {
        $token = $request->header('X-Webhook-Token') ?? $request->input('token');
        if ($token !== config('services.google_ads.webhook_token')) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $payload = $request->all();
        $lead = $ingest->ingest('google_ads', [
            'customer_name' => $payload['full_name']    ?? $payload['name']  ?? null,
            'mobile'        => $payload['phone_number'] ?? $payload['phone'] ?? null,
            'email'         => $payload['email'] ?? null,
            'city'          => $payload['city']  ?? null,
            'remarks'       => $payload['comments'] ?? null,
        ], $payload['lead_id'] ?? null);

        return response()->json(['ok' => true, 'lead_id' => $lead->id], 201);
    }
}
