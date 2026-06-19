<?php

namespace App\Services\WhatsupNinja;

use App\Models\User;
use App\Models\WhatsupninjaConnection;
use Illuminate\Support\Str;
use Throwable;

class ConnectionService
{
    public function __construct(private TemplateSyncService $sync) {}

    /**
     * Create or update the tenant's connection and validate it against
     * WhatsupNinja (login + initial template sync). Returns the saved
     * connection with a fresh status.
     */
    public function connect(User $user, array $credentials): WhatsupninjaConnection
    {
        $ownerId = $user->accountOwnerId();

        $connection = WhatsupninjaConnection::firstOrNew(['owner_id' => $ownerId]);
        $connection->fill([
            'base_url'   => rtrim($credentials['base_url'], '/'),
            'api_key'    => $credentials['api_key'],
            'username'   => $credentials['username'],
            'password'   => $credentials['password'],
            'account_id' => $credentials['account_id'] ?? null,
            'is_active'  => true,
        ]);

        // Generate webhook identifiers once; keep stable across reconnects.
        if (! $connection->webhook_token) {
            $connection->webhook_token = Str::random(40);
        }
        if (! $connection->webhook_secret) {
            $connection->webhook_secret = Str::random(48);
        }

        // Reset cached JWT so the new credentials are used.
        $connection->jwt_token = null;
        $connection->jwt_expires_at = null;
        $connection->save();

        try {
            $client = new WhatsupNinjaClient($connection);
            $client->login();
            $count = $this->sync->sync($connection);

            $connection->forceFill([
                'status'         => 'connected',
                'last_error'     => null,
                'last_synced_at' => now(),
            ])->save();

            return $connection->refresh();
        } catch (Throwable $e) {
            $connection->forceFill([
                'status'     => 'error',
                'last_error' => $e->getMessage(),
            ])->save();

            return $connection->refresh();
        }
    }

    /** Re-validate an existing connection without changing credentials. */
    public function test(WhatsupninjaConnection $connection): bool
    {
        try {
            (new WhatsupNinjaClient($connection))->login();
            $connection->forceFill(['status' => 'connected', 'last_error' => null])->save();
            return true;
        } catch (Throwable $e) {
            $connection->forceFill(['status' => 'error', 'last_error' => $e->getMessage()])->save();
            return false;
        }
    }

    public function disconnect(WhatsupninjaConnection $connection): void
    {
        $connection->forceFill([
            'status'         => 'disconnected',
            'is_active'      => false,
            'jwt_token'      => null,
            'jwt_expires_at' => null,
        ])->save();
    }

    public function forOwner(int $ownerId): ?WhatsupninjaConnection
    {
        return WhatsupninjaConnection::where('owner_id', $ownerId)->first();
    }
}
