<?php

declare(strict_types=1);

namespace App\Support\Supabase;

use App\Support\Supabase\Contracts\AuthenticatesWithSupabase;
use App\Support\Supabase\Exceptions\InvalidCredentialsException;
use App\Support\Supabase\Exceptions\SupabaseAuthException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Throwable;

/**
 * Concrete Supabase Auth (GoTrue) client built on Laravel's HTTP client.
 */
final class SupabaseAuth implements AuthenticatesWithSupabase
{
    public function __construct(
        private readonly HttpFactory $http,
        private readonly string $url,
        private readonly string $anonKey,
        private readonly int $timeout = 10,
    ) {
    }

    public function signInWithPassword(string $email, string $password): array
    {
        $response = $this->send(
            fn (PendingRequest $request): Response => $request->post('/auth/v1/token?grant_type=password', [
                'email' => $email,
                'password' => $password,
            ]),
        );

        if (in_array($response->status(), [400, 401, 403, 422], true)) {
            throw new InvalidCredentialsException();
        }

        $this->ensureSuccessful($response);

        /** @var array<string,mixed> $payload */
        $payload = $response->json();

        if (! isset($payload['access_token'])) {
            throw new SupabaseAuthException('Supabase did not return an access token.');
        }

        return $payload;
    }

    public function getUser(string $accessToken): array
    {
        $response = $this->send(
            fn (PendingRequest $request): Response => $request
                ->withToken($accessToken)
                ->get('/auth/v1/user'),
        );

        $this->ensureSuccessful($response);

        /** @var array<string,mixed> $payload */
        $payload = $response->json() ?? [];

        return $payload;
    }

    public function sendPasswordResetEmail(string $email, ?string $redirectTo = null): void
    {
        $path = '/auth/v1/recover';

        if ($redirectTo !== null && $redirectTo !== '') {
            $path .= '?redirect_to='.rawurlencode($redirectTo);
        }

        $response = $this->send(
            fn (PendingRequest $request): Response => $request->post($path, ['email' => $email]),
        );

        // GoTrue returns 200 whether or not the address exists (no enumeration).
        // Treat 429 (rate limited) as a soft success so the UI still shows the
        // neutral "check your inbox" message rather than leaking timing details.
        if ($response->status() === 429) {
            return;
        }

        $this->ensureSuccessful($response);
    }

    public function signOut(string $accessToken): void
    {
        try {
            $this->send(
                fn (PendingRequest $request): Response => $request
                    ->withToken($accessToken)
                    ->post('/auth/v1/logout'),
            );
        } catch (Throwable) {
            // Best effort only — the local Laravel session is cleared regardless.
        }
    }

    /**
     * Build a pre-configured request bound to the project + anon key.
     */
    private function request(): PendingRequest
    {
        return $this->http
            ->baseUrl($this->url)
            ->timeout($this->timeout)
            ->acceptJson()
            ->asJson()
            ->withHeaders(['apikey' => $this->anonKey]);
    }

    /**
     * Execute a request, translating transport failures into a domain exception.
     *
     * @param  callable(PendingRequest):Response  $callback
     */
    private function send(callable $callback): Response
    {
        try {
            return $callback($this->request());
        } catch (ConnectionException $e) {
            throw new SupabaseAuthException('Could not reach the Supabase auth service.', 0, $e);
        }
    }

    private function ensureSuccessful(Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        throw new SupabaseAuthException(
            "Supabase auth request failed (HTTP {$response->status()}).",
            $response->status(),
        );
    }
}
