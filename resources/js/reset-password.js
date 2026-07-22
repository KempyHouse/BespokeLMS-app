import { createClient } from '@supabase/supabase-js';

/**
 * Completes a Supabase password recovery entirely in the browser.
 *
 * The recovery email link carries the session in the URL fragment, which the
 * server never sees. supabase-js reads it, then updateUser() sets the new
 * password. Config (project URL + publishable key — both public) is passed in
 * via data-* attributes on #reset-config.
 */
const config = document.getElementById('reset-config');
const form = document.getElementById('reset-form');
const statusEl = document.getElementById('reset-status');
const submitBtn = document.getElementById('reset-submit');

function setStatus(message, kind = 'info') {
    if (!statusEl) {
        return;
    }
    statusEl.textContent = message;
    statusEl.dataset.kind = kind;
    statusEl.hidden = false;
}

if (config && form) {
    const supabase = createClient(config.dataset.url, config.dataset.key, {
        auth: {
            detectSessionInUrl: true,
            flowType: 'implicit',
            persistSession: false,
        },
    });

    let hasRecoverySession = false;

    supabase.auth.onAuthStateChange((event) => {
        if (event === 'PASSWORD_RECOVERY' || event === 'SIGNED_IN') {
            hasRecoverySession = true;
        }
    });

    supabase.auth.getSession().then(({ data }) => {
        if (data && data.session) {
            hasRecoverySession = true;
            return;
        }
        if (!hasRecoverySession) {
            setStatus('Open this page from the reset link in your email to set a new password.', 'warn');
        }
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const password = form.password.value;
        const confirmation = form.password_confirmation.value;

        if (password.length < 8) {
            setStatus('Use at least 8 characters.', 'error');
            return;
        }
        if (password !== confirmation) {
            setStatus('The two passwords do not match.', 'error');
            return;
        }

        submitBtn.disabled = true;
        setStatus('Updating your password…');

        const { error } = await supabase.auth.updateUser({ password });

        submitBtn.disabled = false;

        if (error) {
            setStatus(error.message || 'Could not update your password. Try the reset link again.', 'error');
            return;
        }

        setStatus('Password updated. Redirecting you to sign in…', 'success');
        window.setTimeout(() => {
            window.location.href = config.dataset.login;
        }, 1500);
    });
}
