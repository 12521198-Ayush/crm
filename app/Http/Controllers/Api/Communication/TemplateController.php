<?php

namespace App\Http\Controllers\Api\Communication;

use App\Http\Controllers\Controller;
use App\Models\WhatsappTemplate;
use App\Services\WhatsupNinja\ConnectionService;
use App\Services\WhatsupNinja\TemplateSyncService;
use Illuminate\Http\Request;

class TemplateController extends Controller
{
    public function __construct(
        private ConnectionService $connections,
        private TemplateSyncService $sync,
    ) {}

    public function index(Request $request)
    {
        $ownerId = $request->user()->accountOwnerId();

        $q = WhatsappTemplate::where('owner_id', $ownerId);
        if ($request->boolean('active_only', true)) {
            $q->where('is_active', true);
        }
        if ($category = $request->input('category')) {
            $q->where('category', $category);
        }
        if ($search = $request->input('search')) {
            $q->where('name', 'like', "%{$search}%");
        }

        return $q->orderBy('name')->get();
    }

    public function syncNow(Request $request)
    {
        $connection = $this->connections->forOwner($request->user()->accountOwnerId());
        abort_unless($connection, 404, 'No WhatsupNinja connection found');

        try {
            $count = $this->sync->sync($connection);
            return response()->json(['success' => true, 'synced' => $count]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
