<?php

namespace App\Http\Controllers\Api\Communication;

use App\Http\Controllers\Controller;
use App\Models\WhatsappTemplate;
use App\Models\WhatsupninjaConnection;
use App\Services\WhatsupNinja\ConnectionService;
use Illuminate\Http\Request;

class ConnectionController extends Controller
{
    public function __construct(private ConnectionService $service) {}

    public function show(Request $request)
    {
        $connection = $this->service->forOwner($request->user()->accountOwnerId());

        return response()->json([
            'connection' => $connection ? $this->present($connection, $request) : null,
        ]);
    }

    public function connect(Request $request)
    {
        $this->authorizeMaster($request);

        $data = $request->validate([
            'base_url'   => 'required|url',
            'api_key'    => 'required|string',
            'username'   => 'required|string',
            'password'   => 'required|string',
            'account_id' => 'nullable|string',
        ]);

        $connection = $this->service->connect($request->user(), $data);

        return response()->json([
            'connection' => $this->present($connection, $request),
        ], $connection->status === 'connected' ? 200 : 422);
    }

    public function test(Request $request)
    {
        $connection = $this->requireConnection($request);
        $ok = $this->service->test($connection);

        return response()->json([
            'success'    => $ok,
            'connection' => $this->present($connection->refresh(), $request),
        ], $ok ? 200 : 422);
    }

    public function destroy(Request $request)
    {
        $this->authorizeMaster($request);
        $connection = $this->requireConnection($request);
        $this->service->disconnect($connection);

        return response()->json(['success' => true]);
    }

    private function present(WhatsupninjaConnection $c, Request $request): array
    {
        return [
            'id'             => $c->id,
            'base_url'       => $c->base_url,
            'account_id'     => $c->account_id,
            'status'         => $c->status,
            'is_active'      => $c->is_active,
            'last_error'     => $c->last_error,
            'last_synced_at' => $c->last_synced_at,
            'api_key_masked' => $this->mask($c->api_key),
            'template_count' => WhatsappTemplate::where('connection_id', $c->id)->where('is_active', true)->count(),
            // Surfaced so the user can paste these into the WhatsupNinja dashboard.
            'webhook_url'    => url('/api/webhooks/whatsupninja/' . $c->webhook_token),
            'webhook_secret' => $c->webhook_secret,
        ];
    }

    private function mask(?string $value): string
    {
        if (! $value) return '';
        return str_repeat('•', max(0, strlen($value) - 4)) . substr($value, -4);
    }

    private function requireConnection(Request $request): WhatsupninjaConnection
    {
        $connection = $this->service->forOwner($request->user()->accountOwnerId());
        abort_unless($connection, 404, 'No WhatsupNinja connection found');
        return $connection;
    }

    private function authorizeMaster(Request $request): void
    {
        abort_unless($request->user()->isMaster() || $request->user()->isSuperMaster(), 403);
    }
}
