<?php

declare(strict_types=1);

namespace App\Support\Mail;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Crypt;
use Throwable;

/**
 * Resolves the *active* email configuration at send time.
 *
 * Reads the enabled platform transport (organization_id IS NULL) and, if the
 * sending tenant has one, its active sender-identity alias, then decrypts the
 * transport secret server-side. The result is a plain array the {@see TenantMailer}
 * turns into a runtime Laravel mailer — the ciphertext never leaves this layer.
 *
 * Every provider is reached over SMTP so no extra Composer transport package is
 * required to send. (Resend, Postmark and SES all expose an SMTP relay; the
 * secret is the provider's API key / SMTP password.)
 */
final class EmailTransportResolver
{
    public function __construct(
        private readonly HttpFactory $http,
        private readonly string $url,
        private readonly string $serviceRoleKey,
        private readonly int $timeout = 10,
    ) {
    }

    /**
     * The active transport + sender identity for a given tenant, or null when no
     * transport is enabled/connected (so callers can fail soft).
     *
     * @return array{
     *     config: array<string,mixed>,
     *     from_address: ?string,
     *     from_name: ?string,
     *     reply_to: ?string,
     *     integration_id: ?string,
     *     provider: string,
     *     organization_id: ?string
     * }|null
     */
    public function resolve(?string $organizationId = null): ?array
    {
        if ($this->serviceRoleKey === '') {
            return null;
        }

        $transport = $this->enabledTransport();
        if ($transport === null) {
            return null;
        }

        $provider = (string) ($transport['provider'] ?? '');
        $options = is_array($transport['options'] ?? null) ? $transport['options'] : [];

        $secret = null;
        $cipher = $transport['api_key_cipher'] ?? null;
        if (is_string($cipher) && $cipher !== '') {
            try {
                $secret = Crypt::decryptString($cipher);
            } catch (DecryptException) {
                return null; // a secret that cannot be decrypted is unusable
            }
        }

        $config = $this->smtpConfig($provider, $secret, (string) ($transport['base_url'] ?? ''), $options);
        if ($config === null) {
            return null;
        }

        // Sender identity: the tenant's active alias overrides the platform default.
        $fromAddress = $transport['from_address'] ?? null;
        $fromName = $transport['from_name'] ?? null;
        $replyTo = $transport['reply_to'] ?? null;

        $alias = $organizationId !== null ? $this->activeAlias($organizationId) : null;
        if ($alias !== null) {
            $fromAddress = ($alias['from_address'] ?? null) ?: $fromAddress;
            $fromName = ($alias['from_name'] ?? null) ?: $fromName;
            $replyTo = ($alias['reply_to'] ?? null) ?: $replyTo;
        }

        return [
            'config' => $config,
            'from_address' => is_string($fromAddress) && $fromAddress !== '' ? $fromAddress : null,
            'from_name' => is_string($fromName) && $fromName !== '' ? $fromName : null,
            'reply_to' => is_string($replyTo) && $replyTo !== '' ? $replyTo : null,
            'integration_id' => isset($transport['id']) ? (string) $transport['id'] : null,
            'provider' => $provider,
            'organization_id' => $organizationId,
        ];
    }

    /**
     * Map a provider to a Laravel SMTP mailer config, or null if it cannot be
     * built (e.g. SMTP/custom with no host, or any provider with no secret).
     *
     * @param  array<string,mixed>  $options
     * @return array<string,mixed>|null
     */
    private function smtpConfig(string $provider, ?string $secret, string $baseUrl, array $options): ?array
    {
        $username = isset($options['smtp_username']) ? (string) $options['smtp_username'] : '';
        $port = isset($options['smtp_port']) ? (int) $options['smtp_port'] : 0;
        $region = isset($options['region']) ? (string) $options['region'] : '';

        [$host, $port, $username, $password] = match ($provider) {
            'resend' => ['smtp.resend.com', $port ?: 587, 'resend', (string) $secret],
            'postmark' => ['smtp.postmarkapp.com', $port ?: 587, (string) $secret, (string) $secret],
            'ses' => [
                $region !== '' ? "email-smtp.{$region}.amazonaws.com" : '',
                $port ?: 587,
                $username,
                (string) $secret,
            ],
            'smtp', 'custom' => [$this->hostFromBaseUrl($baseUrl), $port ?: 587, $username, (string) $secret],
            default => ['', 0, '', ''],
        };

        if ($host === '' || $password === '') {
            return null;
        }

        return [
            'transport' => 'smtp',
            'scheme' => $port === 465 ? 'smtps' : 'smtp',
            'host' => $host,
            'port' => $port,
            'username' => $username !== '' ? $username : null,
            'password' => $password,
            'timeout' => null,
        ];
    }

    /**
     * Accept either a bare host or a URL in base_url and return the host.
     */
    private function hostFromBaseUrl(string $baseUrl): string
    {
        $baseUrl = trim($baseUrl);
        if ($baseUrl === '') {
            return '';
        }
        if (str_contains($baseUrl, '://')) {
            return (string) (parse_url($baseUrl, PHP_URL_HOST) ?: '');
        }

        return $baseUrl;
    }

    /**
     * @return array<string,mixed>|null
     */
    private function enabledTransport(): ?array
    {
        $rows = $this->get('/rest/v1/email_integrations', [
            'select' => 'id,provider,from_address,from_name,reply_to,base_url,options,api_key_cipher,status',
            'organization_id' => 'is.null',
            'is_enabled' => 'eq.true',
            'order' => 'updated_at.desc',
            'limit' => '1',
        ]);

        return $rows[0] ?? null;
    }

    /**
     * @return array<string,mixed>|null
     */
    private function activeAlias(string $organizationId): ?array
    {
        $rows = $this->get('/rest/v1/tenant_email_aliases', [
            'select' => 'from_address,from_name,reply_to,is_active,is_verified',
            'organization_id' => 'eq.'.$organizationId,
            'is_active' => 'eq.true',
            'limit' => '1',
        ]);

        return $rows[0] ?? null;
    }

    /**
     * @param  array<string,string>  $query
     * @return array<int,array<string,mixed>>
     */
    private function get(string $path, array $query): array
    {
        try {
            $response = $this->request()->get($path, $query);
        } catch (ConnectionException) {
            return [];
        } catch (Throwable) {
            return [];
        }

        if (! $response->successful()) {
            return [];
        }

        /** @var array<int,array<string,mixed>> $rows */
        $rows = $response->json() ?? [];

        return $rows;
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
