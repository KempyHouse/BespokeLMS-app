<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Landing page for the Supabase recovery / magic-link email.
 *
 * Supabase places the recovery session in the URL fragment (which is only
 * readable by the browser, never sent to the server), so the "set a new
 * password" exchange is completed client-side by resources/js/reset-password.js
 * talking to Supabase directly.
 */
class NewPasswordController extends Controller
{
    public function create(): View
    {
        return view('auth.reset-password');
    }
}
