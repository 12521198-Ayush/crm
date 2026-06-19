<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\LeadActivity;
use App\Models\WhatsappMessageLog;
use App\Models\WhatsappWebhookEvent;
use App\Models\WhatsupninjaConnection;
use Illuminate\Http\Request;

class WhatsupNinjaWebhookController extends Controller
{
    /** Receives delivery-status (and incoming-message) callbacks from WhatsupNinja. */
    public function handle(Request $request, string $token)
    {
        $connection = WhatsupninjaConnection::where('webhook_token', $token)->first();
        if (! $connection) {
            return response()->json(['ok' => false], 404);
        }

        $eventType = $request->header('X-Webhook-Event', $request->input('event'));
        $payload   = $request->all();
        $messageId = (string) ($payload['data']['message_id'] ?? '');
        $valid     = $this->verifySignature($request, $connection->webhook_secret);

        $event = WhatsappWebhookEvent::create([
            'owner_id'          => $connection->owner_id,
            'connection_id'     => $connection->id,
            'event_type'        => $eventType,
            'remote_message_id' => $messageId ?: null,
            'payload'           => $payload,
            'signature_valid'   => $valid,
            'received_at'       => now(),
        ]);

        // Only mutate state for authentic events.
        if ($valid && $eventType === 'message_status_update') {
            $this->applyStatus($connection, $payload['data'] ?? []);
            $event->update(['processed' => true]);
        }

        return response()->json(['ok' => true]);
    }

    private function applyStatus(WhatsupninjaConnection $connection, array $data): void
    {
        $messageId = (string) ($data['message_id'] ?? '');
        $status    = strtolower((string) ($data['status'] ?? ''));
        if (! $messageId || ! in_array($status, ['sent', 'delivered', 'read', 'failed'], true)) {
            return;
        }

        $log = WhatsappMessageLog::where('connection_id', $connection->id)
            ->where('remote_message_id', $messageId)
            ->first();
        if (! $log) {
            return;
        }

        $timestampField = [
            'sent' => 'sent_at', 'delivered' => 'delivered_at',
            'read' => 'read_at', 'failed' => 'failed_at',
        ][$status];

        // Don't regress status (read is terminal-positive; failed is terminal-negative).
        if ($this->rank($status) < $this->rank($log->status) && $log->status !== 'failed') {
            return;
        }

        $log->forceFill([
            'status'          => $status,
            $timestampField   => now(),
            'error_message'   => $data['error'] ?? $log->error_message,
        ])->save();

        $this->touchTimeline($log, $status);
    }

    /** Update the matching WhatsApp timeline entry's status meta. */
    private function touchTimeline(WhatsappMessageLog $log, string $status): void
    {
        if (! $log->lead_id) {
            return;
        }
        $activity = LeadActivity::where('lead_id', $log->lead_id)
            ->where('type', LeadActivity::TYPE_WHATSAPP)
            ->whereJsonContains('meta->message_log_id', $log->id)
            ->latest('id')->first();

        if ($activity) {
            $meta = $activity->meta ?? [];
            $meta['status'] = $status;
            $activity->update(['meta' => $meta, 'title' => 'WhatsApp template ' . $status]);
        }
    }

    private function rank(string $status): int
    {
        return ['queued' => 0, 'sent' => 1, 'delivered' => 2, 'read' => 3, 'failed' => 1][$status] ?? 0;
    }

    private function verifySignature(Request $request, ?string $secret): bool
    {
        if (! $secret) {
            return false;
        }
        $header = $request->header('X-Webhook-Signature', '');
        if (! str_starts_with($header, 'sha256=')) {
            return false;
        }
        $expected = hash_hmac('sha256', $request->getContent(), $secret);
        return hash_equals($expected, substr($header, 7));
    }
}
