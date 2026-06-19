<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadActivity;
use Illuminate\Http\Request;

class LeadActivityController extends Controller
{
    public function index(Request $request, Lead $lead)
    {
        return $lead->activities()->with('user:id,name,role')->paginate(20);
    }

    public function store(Request $request, Lead $lead)
    {
        $data = $request->validate([
            'type'         => 'required|in:note,call,whatsapp,meeting,follow_up,event',
            'title'        => 'nullable|string|max:191',
            'body'         => 'nullable|string',
            'scheduled_at' => 'nullable|date',
            'meta'         => 'nullable|array',
        ]);
        $data['lead_id'] = $lead->id;
        $data['user_id'] = $request->user()->id;
        $activity = LeadActivity::create($data);

        if ($data['type'] === LeadActivity::TYPE_FOLLOWUP && ! empty($data['scheduled_at'])) {
            $lead->update(['follow_up_at' => $data['scheduled_at']]);
        }

        return response()->json($activity->load('user:id,name,role'), 201);
    }
}
