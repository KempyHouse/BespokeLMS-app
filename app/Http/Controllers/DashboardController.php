<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Auth\SupabaseUser;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Serves the BespokeLMS dashboard behind the authentication gate.
 *
 * Renders the frozen prototype UI (resources/dashboard/app.html) and appends a
 * small script that injects the *real* signed-in user and makes the workspace
 * navigation role-aware:
 *
 *   - the platform owner sees the "Admin" workspace relabelled "Platform", linking
 *     to the owner-only console (/platform);
 *   - operator/client admins keep the in-app "Admin" workspace and land on it;
 *   - team managers see only "My" and "Team";
 *   - learners see only "My".
 *
 * The frozen HTML file is left byte-for-byte unchanged; all behaviour is layered on
 * through this injection. Data access is independently enforced by Supabase RLS, so
 * hiding a control never grants access on its own.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        /** @var SupabaseUser $user */
        $user = $request->user();

        $html = (string) file_get_contents(resource_path('dashboard/app.html'));

        $payload = json_encode([
            'name' => $user->displayName(),
            'email' => $user->email,
            'role' => $user->roleLabel(),
            'roleKey' => $user->role,
            'isPlatformOwner' => $user->isPlatformOwner(),
            'organization' => $user->organizationName,
            'platformUrl' => $user->isPlatformOwner() ? route('platform.home') : null,
            'logoutUrl' => route('logout'),
            'csrf' => csrf_token(),
        ], JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES);

        $injection = <<<HTML
<script>
window.__BESPOKE_USER__ = {$payload};
(function () {
    var u = window.__BESPOKE_USER__;
    if (!u) return;

    // Bind the real signed-in user onto both header identities.
    if (window.IDENTITY) {
        ['admin', 'default'].forEach(function (k) {
            window.IDENTITY[k] = window.IDENTITY[k] || {};
            window.IDENTITY[k].name = u.name;
            window.IDENTITY[k].email = u.email;
            window.IDENTITY[k].role = u.role;
        });
    }

    // Which workspaces this role may use.
    var allowed = { my: true, team: false, admin: false };
    if (u.roleKey === 'bespokelms_owner' || u.roleKey === 'lms_operator_admin' || u.roleKey === 'client_admin') {
        allowed = { my: true, team: true, admin: true };
    } else if (u.roleKey === 'team_manager') {
        allowed = { my: true, team: true, admin: false };
    }

    function buttonsFor(ws) {
        return Array.prototype.slice.call(document.querySelectorAll('button')).filter(function (b) {
            return (b.getAttribute('onclick') || '').indexOf("switchWorkspace('" + ws + "')") !== -1;
        });
    }

    // The "Admin" workspace means different things by altitude: for the platform
    // owner it is relabelled "Platform" and links to the owner-only console; for
    // tenant admins it stays the in-app Admin workspace.
    buttonsFor('admin').forEach(function (b) {
        if (!allowed.admin) { b.style.display = 'none'; return; }
        if (u.isPlatformOwner) {
            b.textContent = 'Platform';
            if (u.platformUrl) {
                b.setAttribute('onclick', "window.location.href='" + u.platformUrl + "'");
            }
        }
    });
    buttonsFor('team').forEach(function (b) {
        if (!allowed.team) { b.style.display = 'none'; }
    });

    // Land tenant admins on their Admin console and team managers on Team; the
    // owner and learners stay on the default ("my") view the page already shows.
    try {
        if (typeof window.switchWorkspace === 'function') {
            if (!u.isPlatformOwner && allowed.admin) {
                window.switchWorkspace('admin');
            } else if (allowed.team && !allowed.admin) {
                window.switchWorkspace('team');
            }
        }
    } catch (e) {}

    // Replace the mock "Log out" with a real Laravel logout POST.
    window.profileLogout = function () {
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = u.logoutUrl;
        var token = document.createElement('input');
        token.type = 'hidden';
        token.name = '_token';
        token.value = u.csrf;
        form.appendChild(token);
        document.body.appendChild(form);
        form.submit();
    };
})();
</script>
HTML;

        $html = str_replace('</body>', $injection."\n</body>", $html);

        return response($html)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Cache-Control', 'private, no-store');
    }
}
