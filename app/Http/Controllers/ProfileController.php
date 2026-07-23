<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Auth\SupabaseUser;
use App\Auth\SupabaseUserProvider;
use App\Support\Supabase\Contracts\WritesProfiles;
use App\Support\Supabase\Exceptions\SupabaseAuthException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * A user's own profile: identity fields (first name / surname / job title) and
 * their avatar image. Every write is scoped to the signed-in user's own profile
 * id — there is no cross-user access here — and the session identity snapshot is
 * kept in step so changes show immediately without a re-login.
 */
final class ProfileController extends Controller
{
    /** Allowed avatar mime types → file extension. */
    private const AVATAR_TYPES = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/webp' => 'webp',
    ];

    public function edit(Request $request): View
    {
        /** @var SupabaseUser $user */
        $user = $request->user();

        return view('profile.edit', ['user' => $user]);
    }

    public function update(Request $request, WritesProfiles $profiles): RedirectResponse
    {
        /** @var SupabaseUser $user */
        $user = $request->user();

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['nullable', 'string', 'max:80'],
            'job_title' => ['nullable', 'string', 'max:120'],
        ]);

        if ($user->profileId === null || $user->profileId === '') {
            return redirect()->route('profile.edit')->with('profileError', 'Your profile could not be identified.');
        }

        $firstName = trim((string) $validated['first_name']);
        $lastName = trim((string) ($validated['last_name'] ?? ''));
        $jobTitle = trim((string) ($validated['job_title'] ?? ''));
        $jobTitle = $jobTitle !== '' ? $jobTitle : null;

        try {
            $profiles->updateDetails($user->profileId, $firstName, $lastName, $jobTitle);
        } catch (SupabaseAuthException $e) {
            report($e);

            return redirect()->route('profile.edit')->with('profileError', 'Your profile could not be saved right now. Please try again shortly.');
        }

        $this->patchSnapshot($request, [
            'firstName' => $firstName,
            'lastName' => $lastName,
            'name' => trim($firstName.' '.$lastName),
            'jobTitle' => $jobTitle,
        ]);

        return redirect()->route('profile.edit')->with('status', 'Profile updated.');
    }

    public function updateAvatar(Request $request, WritesProfiles $profiles): RedirectResponse
    {
        /** @var SupabaseUser $user */
        $user = $request->user();

        $request->validate([
            'avatar' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
        ]);

        if ($user->profileId === null || $user->profileId === '') {
            return redirect()->route('profile.edit')->with('profileError', 'Your profile could not be identified.');
        }

        $file = $request->file('avatar');
        $mime = (string) $file->getMimeType();
        $ext = self::AVATAR_TYPES[$mime] ?? 'png';
        $contents = (string) file_get_contents($file->getRealPath());

        // A fresh path each save busts the CDN/browser cache of the public URL.
        $objectPath = $user->profileId.'/avatar-'.bin2hex(random_bytes(8)).'.'.$ext;

        try {
            $profiles->uploadAvatar($objectPath, $contents, $mime);
            $profiles->updateAvatarPath($user->profileId, $objectPath);
        } catch (SupabaseAuthException $e) {
            report($e);

            return redirect()->route('profile.edit')->with('profileError', 'Your photo could not be uploaded right now. Please try again shortly.');
        }

        $this->patchSnapshot($request, ['avatarPath' => $objectPath]);

        return redirect()->route('profile.edit')->with('status', 'Profile photo updated.');
    }

    public function removeAvatar(Request $request, WritesProfiles $profiles): RedirectResponse
    {
        /** @var SupabaseUser $user */
        $user = $request->user();

        if ($user->profileId === null || $user->profileId === '') {
            return redirect()->route('profile.edit')->with('profileError', 'Your profile could not be identified.');
        }

        try {
            $profiles->updateAvatarPath($user->profileId, null);
        } catch (SupabaseAuthException $e) {
            report($e);

            return redirect()->route('profile.edit')->with('profileError', 'Your photo could not be removed right now. Please try again shortly.');
        }

        $this->patchSnapshot($request, ['avatarPath' => null]);

        return redirect()->route('profile.edit')->with('status', 'Profile photo removed.');
    }

    /**
     * Merge changed identity fields into the server-side session snapshot so the
     * header/dropdown reflect them without waiting for the next sign-in.
     *
     * @param  array<string,mixed>  $changes
     */
    private function patchSnapshot(Request $request, array $changes): void
    {
        $snapshot = $request->session()->get(SupabaseUserProvider::SESSION_KEY);
        if (is_array($snapshot)) {
            foreach ($changes as $key => $value) {
                $snapshot[$key] = $value;
            }
            $request->session()->put(SupabaseUserProvider::SESSION_KEY, $snapshot);
        }
    }
}
