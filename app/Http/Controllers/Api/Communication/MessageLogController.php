<?php

namespace App\Http\Controllers\Api\Communication;

use App\Http\Controllers\Controller;
use App\Models\WhatsappMessageLog;
use Illuminate\Http\Request;

class MessageLogController extends Controller
{
    public function index(Request $request)
    {
        $ownerId = $request->user()->accountOwnerId();

        $q = WhatsappMessageLog::with(['lead:id,customer_name,mobile', 'template:id,name'])
            ->where('owner_id', $ownerId);

        if ($status = $request->input('status')) {
            $q->where('status', $status);
        }
        if ($event = $request->input('trigger_event')) {
            $q->where('trigger_event', $event);
        }
        if ($search = $request->input('search')) {
            $q->where(fn ($w) => $w->where('recipient', 'like', "%{$search}%")
                ->orWhereHas('lead', fn ($l) => $l->where('customer_name', 'like', "%{$search}%")));
        }
        if ($from = $request->input('from')) {
            $q->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->input('to')) {
            $q->whereDate('created_at', '<=', $to);
        }

        return $q->orderByDesc('id')->paginate((int) $request->input('per_page', 20));
    }

    public function show(Request $request, WhatsappMessageLog $whatsappMessageLog)
    {
        abort_unless($whatsappMessageLog->owner_id === $request->user()->accountOwnerId(), 403);
        return $whatsappMessageLog->load(['lead:id,customer_name,mobile', 'template:id,name', 'rule:id,name']);
    }
}
