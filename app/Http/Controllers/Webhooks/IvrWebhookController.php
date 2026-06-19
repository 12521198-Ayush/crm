<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\LeadIngestService;
use Illuminate\Http\Request;

class IvrWebhookController extends Controller
{
    public function handle(Request $request, LeadIngestService $ingest)
    {
        $token = $request->header('X-Webhook-Token') ?? $request->input('token');
        if ($token !== config('services.ivr.webhook_token')) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $payload = $request->all();
        $lead = $ingest->ingest('ivr', [
            'customer_name' => $payload['caller_name'] ?? $payload['name'] ?? ('Caller ' . ($payload['caller_number'] ?? '')),
            'mobile'        => $payload['caller_number'] ?? $payload['phone'] ?? null,
            'remarks'       => 'IVR call received. Duration: ' . ($payload['duration'] ?? 'n/a'),
        ], $payload['call_id'] ?? null);

        return response()->json(['ok' => true, 'lead_id' => $lead->id], 201);
    }
}
