<?php

namespace App\Services\WhatsupNinja;

use App\Models\WhatsupninjaConnection;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Thin HTTP wrapper around the WhatsupNinja client API.
 *
 * Every request carries the `apikey` header; template/send endpoints also
 * require a JWT bearer token obtained from POST /api/login (username+password).
 * The token is cached on the connection row and transparently refreshed on 401.
 *
 * IMPORTANT: credentials are never written to logs.
 */
class WhatsupNinjaClient
{
    public function __construct(private WhatsupninjaConnection $connection) {}

    /** Authenticate and cache a JWT on the connection. Returns the token. */
    public function login(): string
    {
        $res = $this->http(withToken: false)->post($this->url('/login'), [
            'username' => $this->connection->username,
            'password' => $this->connection->password,
        ]);

        $token = $res->json('data.token') ?? $res->json('token');
        if (! $res->successful() || ! $token) {
            throw new RuntimeException($this->errorMessage($res, 'Login failed'));
        }

        $this->connection->forceFill([
            'jwt_token'      => $token,
            // The WhatsupNinja JWT ttl is open-ended; assume ~24h and refresh on 401.
            'jwt_expires_at' => now()->addHours(24),
        ])->save();

        return $token;
    }

    /** Fetch every template across all pages. Returns a flat array of template arrays. */
    public function templates(): array
    {
        $all  = [];
        $page = 1;

        do {
            $res = $this->authedGet('/whatsapp-templates', ['page' => $page]);
            $data = $res->json('data') ?? [];
            $batch = $data['template'] ?? [];
            foreach ($batch as $t) {
                $all[] = $t;
            }
            $paginate = $data['paginate'] ?? [];
            $last = (int) ($paginate['last_page'] ?? 1);
            $page++;
        } while ($page <= $last && $page < 100);

        return $all;
    }

    /**
     * Send an approved template to a phone number (contact auto-created).
     *
     * @param array $bodyValues  Ordered list of body placeholder values.
     * @param array $buttonValues Ordered list of dynamic button values.
     */
    public function sendTemplateByPhone(
        string $phone,
        string $templateName,
        array $bodyValues = [],
        array $buttonValues = [],
        ?string $contactName = null
    ): array {
        $payload = [
            'phone'         => $phone,
            'template_name' => $templateName,
            'contact_name'  => $contactName,
            'body_values'   => $this->indexed($bodyValues),
            'body_matchs'   => $this->matchs($bodyValues),
        ];
        if ($buttonValues) {
            $payload['button_values'] = $this->indexed($buttonValues);
            $payload['button_matchs'] = $this->matchs($buttonValues);
        }

        $res = $this->authedPost('/whatsapp/send-template-by-phone', $payload);

        if (! $res->successful() || $res->json('success') === false) {
            throw new RuntimeException($this->errorMessage($res, 'Send failed'));
        }

        return [
            'message_id' => $this->extractMessageId($res),
            'response'   => $res->json(),
        ];
    }

    // ---- internals -------------------------------------------------------

    private function authedGet(string $path, array $query = []): Response
    {
        $res = $this->http()->get($this->url($path), $query);
        if ($res->status() === 401) {
            $this->login();
            $res = $this->http()->get($this->url($path), $query);
        }
        return $res;
    }

    private function authedPost(string $path, array $body): Response
    {
        $body = array_filter($body, fn ($v) => $v !== null);
        // asForm() encodes the 1-indexed body_values/body_matchs maps as
        // body_values[1]=..., which the endpoint parses back into arrays.
        $res = $this->http()->asForm()->post($this->url($path), $body);
        if ($res->status() === 401) {
            $this->login();
            $res = $this->http()->asForm()->post($this->url($path), $body);
        }
        return $res;
    }

    private function http(bool $withToken = true)
    {
        $req = Http::withHeaders(['apikey' => $this->connection->api_key, 'Accept' => 'application/json'])
            ->timeout((int) config('whatsupninja.request_timeout', 20));

        if ($withToken) {
            $token = $this->connection->jwt_token ?: $this->login();
            $req = $req->withToken($token);
        }
        return $req;
    }

    private function url(string $path): string
    {
        $base = rtrim($this->connection->base_url, '/');
        $apiPath = trim((string) config('whatsupninja.api_path', '/api'), '/');
        return "{$base}/{$apiPath}/" . ltrim($path, '/');
    }

    /** Convert an ordered list into a 1-indexed map as WhatsupNinja expects. */
    private function indexed(array $values): array
    {
        $out = [];
        foreach (array_values($values) as $i => $v) {
            $out[$i + 1] = (string) $v;
        }
        return $out;
    }

    private function matchs(array $values): array
    {
        $out = [];
        foreach (array_values($values) as $i => $v) {
            $out[$i + 1] = 'input_value';
        }
        return $out;
    }

    private function extractMessageId(Response $res): ?string
    {
        return (string) (
            $res->json('data.message_id')
            ?? $res->json('data.id')
            ?? $res->json('data.message.id')
            ?? ''
        ) ?: null;
    }

    private function errorMessage(Response $res, string $fallback): string
    {
        return $res->json('message') ?: ($fallback . ' (HTTP ' . $res->status() . ')');
    }
}
