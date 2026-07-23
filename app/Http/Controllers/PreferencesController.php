<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Auth\SupabaseUser;
use App\Auth\SupabaseUserProvider;
use App\Support\Supabase\Contracts\WritesProfiles;
use App\Support\Supabase\Exceptions\SupabaseAuthException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

/**
 * Per-user preferences. The theme toggle applies instantly on the client; this
 * endpoint persists the choice to the profile and keeps the session identity
 * snapshot in step so it survives reloads.
 */
final class PreferencesController extends Controller
{
    public function updateTheme(Request $request, WritesProfiles $profiles): Response
    {
        $validated = $request->validate([
            'theme' => ['required', Rule::in(['light', 'dark', 'system'])],
        ]);
        $theme = (string) $validated['theme'];

        $user = $request->user();

        if ($user instanceof SupabaseUser && $user->profileId !== null && $user->profileId !== '') {
            try {
                $profiles->updateThemePreference($user->profileId, $theme);

                $snapshot = $request->session()->get(SupabaseUserProvider::SESSION_KEY);
                if (is_array($snapshot)) {
                    $snapshot['themePreference'] = $theme;
                    $request->session()->put(SupabaseUserProvider::SESSION_KEY, $snapshot);
                }
            } catch (SupabaseAuthException $e) {
                // Best effort: the client has already applied the theme.
                report($e);
            }
        }

        return response()->noContent();
    }
}
