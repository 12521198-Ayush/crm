<?php

namespace App\Services;

use App\Events\LeadCreated;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadSource;
use App\Models\LeadStatus;

class LeadIngestService
{
    public function ingest(string $sourceSlug, array $payload, ?string $externalId = null): Lead
    {
        $source = LeadSource::where('slug', $sourceSlug)->first();
        $status = LeadStatus::where('slug', 'fresh')->first()
               ?? LeadStatus::where('slug', 'untouched')->first();

        $lead = Lead::create([
            'customer_name' => $payload['customer_name'] ?? ($payload['name'] ?? 'Unknown'),
            'mobile'        => $payload['mobile'] ?? $payload['phone'] ?? null,
            'email'         => $payload['email'] ?? null,
            'city'          => $payload['city'] ?? null,
            'remarks'       => $payload['remarks'] ?? null,
            'source_id'     => optional($source)->id,
            'sub_source'    => $payload['sub_source'] ?? $payload['ad_set_name'] ?? $payload['campaign_name'] ?? null,
            'status_id'     => optional($status)->id,
            'external_id'   => $externalId,
            'campaign_name' => $payload['campaign_name'] ?? null,
            'ad_set_name'   => $payload['ad_set_name'] ?? null,
            'ad_name'       => $payload['ad_name'] ?? null,
            'form_name'     => $payload['form_name'] ?? null,
            'raw_payload'   => $payload,
        ]);

        LeadActivity::create([
            'lead_id' => $lead->id,
            'type'    => LeadActivity::TYPE_NOTE,
            'title'   => 'Lead ingested from ' . ($source?->name ?? $sourceSlug),
        ]);

        LeadCreated::dispatch($lead, 'lead.created');

        return $lead;
    }
}
