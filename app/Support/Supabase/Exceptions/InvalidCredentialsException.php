<?php

declare(strict_types=1);

namespace App\Support\Supabase\Exceptions;

/**
 * Raised when Supabase rejects an email/password pair.
 */
class InvalidCredentialsException extends SupabaseAuthException
{
    public function __construct(string $message = 'Invalid login credentials.')
    {
        parent::__construct($message);
    }
}
