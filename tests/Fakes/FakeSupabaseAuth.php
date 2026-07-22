<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Support\Supabase\Contracts\AuthenticatesWithSupabase;
use App\Support\Supabase\Exceptions\InvalidCredentialsException;

/**
 * In-memory Supabase Auth double for tests — no network involved.
 */
final class FakeSupabaseAuth implements AuthenticatesWithSupabase
{
    /** @var array<int,string> */
    public array $recoveredEmails = [];

    /** @var array<int,string> */
    public array $signedOutTokens = [];

    /**
     * @param  array<string,string>  $valid    Map of email => password.
     * @param  array<string,mixed>   $session  The GoTrue token payload to return on success.
     */
    public function __construct(
        private array $valid = [],
        private array $session = [],
    ) {
    }

    public function signInWithPassword(string $email, string $password): array
    {
        if (($this->valid[$email] ?? null) !== $password) {
            throw new InvalidCredentialsException();
        }

        return $this->session;
    }

    public function getUser(string $accessToken): array
    {
        return is_array($this->session['user'] ?? null) ? $this->session['user'] : [];
    }

    public function sendPasswordResetEmail(string $email, ?string $redirectTo = null): void
    {
        $this->recoveredEmails[] = $email;
    }

    public function signOut(string $accessToken): void
    {
        $this->signedOutTokens[] = $accessToken;
    }
}
