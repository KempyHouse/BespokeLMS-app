<?php

declare(strict_types=1);

namespace App\Support\Supabase\Exceptions;

use RuntimeException;

/**
 * Raised when the Supabase Auth service errors or cannot be reached.
 */
class SupabaseAuthException extends RuntimeException
{
}
