<?php

declare(strict_types=1);

namespace App\Support\Mail;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use Throwable;

/**
 * Appends rows to the email delivery ledger (`email_send_logs`) via PostgREST
 * using the server-side service-role key. Best-effort: logging never throws, so
 * a ledger write can't fail an email send.
 *
 * Only the recipient's domain is stored — never the full address — so no learner
 * PII lands in the log.
 */
final class EmailLogWriter
{
    public function __construct(
        private readonly HttpFactory $http,
        private readonly string $url,
        private readonly string $serviceRoleKey,
        private readonly int $timeout = 10,
    ) {
    }

    /**
     * @param  array<string,mixed>  $meta
     */
    public function record(
        ?string $integrationId,
        ?string $organizationId,
        ?string $feature,
        string $event,
        ?string $recipient = null,
        ?string $providerMessageId = null,
        array $meta = [],
    ): void {
        if ($this->serviceRoleKey === '') {
            return;
        }

        $payload = [
            'integration_id' => $integrationId,
            'organization_id' => $organizationId,
            'feature' => $feature,
            'event' => $event,
            'to_domain' => $this->domainOf($recipient),
            'provider_message_id' => $providerMessageId,
            'meta' => $meta === [] ? (object) [] : $meta,
        ];

        try {
            $this->request()
                ->withHeaders(['Prefer' => 'return=minimal'])
                ->post('/rest/v1/email_send_logs', $payload);
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function domainOf(?string $recipient): ?string
    {
        if (! is_string($recipient) || ! str_contains($recipient, '@')) {
            return null;
        }

        return strtolower(substr($recipient, strrpos($recipient, '@') + 1)) ?: null;
    }

    private function request(): PendingRequest
    {
        return $this->http
            ->baseUrl($this->url)
            ->timeout($this->timeout)
            ->acceptJson()
            ->withHeaders(['apikey' => $this->serviceRoleKey])
            ->withToken($this->serviceRoleKey);
    }
}
