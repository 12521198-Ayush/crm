<?php

namespace App\Http\Controllers\Api\Communication;

use App\Http\Controllers\Controller;
use App\Models\WhatsappMessageLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $ownerId = $request->user()->accountOwnerId();
        $base = WhatsappMessageLog::where('owner_id', $ownerId);

        if ($from = $request->input('from')) $base->whereDate('created_at', '>=', $from);
        if ($to = $request->input('to'))     $base->whereDate('created_at', '<=', $to);

        $byStatus = (clone $base)->select('status', DB::raw('count(*) as c'))
            ->groupBy('status')->pluck('c', 'status');

        $total     = (int) $byStatus->sum();
        $sent      = (int) ($byStatus['sent'] ?? 0) + (int) ($byStatus['delivered'] ?? 0) + (int) ($byStatus['read'] ?? 0);
        $delivered = (int) ($byStatus['delivered'] ?? 0) + (int) ($byStatus['read'] ?? 0);
        $read      = (int) ($byStatus['read'] ?? 0);
        $failed    = (int) ($byStatus['failed'] ?? 0);

        $perTemplate = (clone $base)->with('template:id,name')
            ->select('template_id', DB::raw('count(*) as c'))
            ->groupBy('template_id')->orderByDesc('c')->limit(10)->get()
            ->map(fn ($r) => ['name' => $r->template?->name ?? '—', 'count' => (int) $r->c]);

        $perEvent = (clone $base)->select('trigger_event', DB::raw('count(*) as c'))
            ->groupBy('trigger_event')->orderByDesc('c')->get()
            ->map(fn ($r) => ['event' => $r->trigger_event ?? '—', 'count' => (int) $r->c]);

        $monthlySent = (int) WhatsappMessageLog::where('owner_id', $ownerId)
            ->whereIn('status', ['sent', 'delivered', 'read'])
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();

        return response()->json([
            'totals' => [
                'total'         => $total,
                'sent'          => $sent,
                'delivered'     => $delivered,
                'read'          => $read,
                'failed'        => $failed,
                'queued'        => (int) ($byStatus['queued'] ?? 0),
                'delivery_rate' => $sent ? round($delivered / $sent * 100, 1) : 0,
                'read_rate'     => $delivered ? round($read / $delivered * 100, 1) : 0,
            ],
            'per_template'  => $perTemplate,
            'per_event'     => $perEvent,
            'monthly_sent'  => $monthlySent, // monetization/usage signal
        ]);
    }
}
