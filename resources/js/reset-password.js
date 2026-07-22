import { createClient } from '@supabase/supabase-js';

/**
 * Reset-password screen behaviour — runs entirely in the browser.
 *
 *  - Completes the Supabase recovery (updateUser) using the session carried in
 *    the email link's URL fragment (which the server never sees).
 *  - Generates a strong random password on demand (Web Crypto).
 *  - Shows a live password-strength indicator.
 *  - Live-verifies that the two password fields are identical.
 *
 * The password only ever lives in the input fields and in the HTTPS request
 * body supabase-js sends — it is never placed in the URL. The form has no
 * name attributes and submits are always intercepted (event.preventDefault).
 */
const config = document.getElementById('reset-config');
const form = document.getElementById('reset-form');
const statusEl = document.getElementById('reset-status');
const submitBtn = document.getElementById('reset-submit');
const passwordInput = document.getElementById('password');
const confirmInput = document.getElementById('password_confirmation');

function setStatus(message, kind = 'info') {
    if (!statusEl) {
        return;
    }
    statusEl.textContent = message;
    statusEl.dataset.kind = kind;
    statusEl.hidden = false;
}

/* -------------------------------------------------------------------------
 * Strong password generation (Web Crypto — cryptographically random, unbiased)
 * ---------------------------------------------------------------------- */
function randomInt(max) {
    // Unbiased integer in [0, max) via rejection sampling.
    const limit = Math.floor(0xffffffff / max) * max;
    const buffer = new Uint32Array(1);
    let value;
    do {
        crypto.getRandomValues(buffer);
        value = buffer[0];
    } while (value >= limit);
    return value % max;
}

function generateStrongPassword(length = 20) {
    // Ambiguous characters (l, I, O, 0, 1) are excluded for legibility.
    const groups = [
        'abcdefghijkmnopqrstuvwxyz',
        'ABCDEFGHJKLMNPQRSTUVWXYZ',
        '23456789',
        '!@#$%^&*()-_=+?',
    ];
    const all = groups.join('');
    const chars = groups.map((set) => set[randomInt(set.length)]);
    while (chars.length < length) {
        chars.push(all[randomInt(all.length)]);
    }
    // Fisher–Yates shuffle so the guaranteed characters aren't in fixed slots.
    for (let i = chars.length - 1; i > 0; i -= 1) {
        const j = randomInt(i + 1);
        [chars[i], chars[j]] = [chars[j], chars[i]];
    }
    return chars.join('');
}

/* -------------------------------------------------------------------------
 * Password strength indicator
 * ---------------------------------------------------------------------- */
const strengthWrap = document.getElementById('password-strength');
const strengthBars = strengthWrap ? [...strengthWrap.querySelectorAll('[data-strength-bar]')] : [];
const strengthMeter = strengthWrap ? strengthWrap.querySelector('[role="progressbar"]') : null;
const strengthLabel = strengthWrap ? strengthWrap.querySelector('[data-strength-label]') : null;

// Every colour is a design-system token utility (no raw values).
const BAR_COLORS = ['bg-brand-line', 'bg-rag-red', 'bg-rag-amber', 'bg-rag-green', 'bg-brand-accent'];
const TEXT_COLORS = ['text-brand-ink-faint', 'text-rag-red', 'text-rag-amber', 'text-rag-green', 'text-brand-accent'];
const STRENGTH = [
    { label: 'Too short', fill: 1, bar: 'bg-rag-red', text: 'text-rag-red' },
    { label: 'Weak', fill: 1, bar: 'bg-rag-red', text: 'text-rag-red' },
    { label: 'Fair', fill: 2, bar: 'bg-rag-amber', text: 'text-rag-amber' },
    { label: 'Good', fill: 3, bar: 'bg-rag-green', text: 'text-rag-green' },
    { label: 'Strong', fill: 4, bar: 'bg-brand-accent', text: 'text-brand-accent' },
];

function scorePassword(pw) {
    if (pw.length < 8) {
        return 0;
    }
    let score = 1;
    if (pw.length >= 12) {
        score += 1;
    }
    if (pw.length >= 16) {
        score += 1;
    }
    let classes = 0;
    if (/[a-z]/.test(pw)) {
        classes += 1;
    }
    if (/[A-Z]/.test(pw)) {
        classes += 1;
    }
    if (/[0-9]/.test(pw)) {
        classes += 1;
    }
    if (/[^A-Za-z0-9]/.test(pw)) {
        classes += 1;
    }
    if (classes >= 3) {
        score += 1;
    }
    return Math.min(score, 4);
}

function updateStrength() {
    if (!strengthWrap) {
        return;
    }
    const pw = passwordInput.value;
    if (!pw) {
        strengthWrap.hidden = true;
        return;
    }
    strengthWrap.hidden = false;

    const level = STRENGTH[scorePassword(pw)];

    strengthBars.forEach((bar, index) => {
        BAR_COLORS.forEach((colour) => bar.classList.remove(colour));
        bar.classList.add(index < level.fill ? level.bar : 'bg-brand-line');
    });

    if (strengthMeter) {
        strengthMeter.setAttribute('aria-valuenow', String(STRENGTH.indexOf(level)));
        strengthMeter.setAttribute('aria-valuetext', level.label);
    }
    if (strengthLabel) {
        TEXT_COLORS.forEach((colour) => strengthLabel.classList.remove(colour));
        strengthLabel.classList.add(level.text);
        strengthLabel.textContent = `Password strength: ${level.label}`;
    }
}

/* -------------------------------------------------------------------------
 * Live "passwords match" verification
 * ---------------------------------------------------------------------- */
const matchEl = document.getElementById('password-match');
const MATCH_COLORS = ['text-brand-ink-faint', 'text-rag-amber', 'text-rag-green'];

function updateMatch() {
    if (!matchEl) {
        return;
    }
    const confirmation = confirmInput.value;
    MATCH_COLORS.forEach((colour) => matchEl.classList.remove(colour));

    if (!confirmation) {
        matchEl.hidden = true;
        confirmInput.removeAttribute('aria-invalid');
        return;
    }

    matchEl.hidden = false;
    if (passwordInput.value === confirmation) {
        matchEl.textContent = 'Passwords match.';
        matchEl.classList.add('text-rag-green');
        confirmInput.removeAttribute('aria-invalid');
    } else {
        matchEl.textContent = 'Passwords do not match yet.';
        matchEl.classList.add('text-rag-amber');
        confirmInput.setAttribute('aria-invalid', 'true');
    }
}

/* -------------------------------------------------------------------------
 * Reveal the new password and keep the Show/Hide toggle button in sync.
 * ---------------------------------------------------------------------- */
function revealNewPassword() {
    const toggle = document.querySelector('[data-toggle-password="password"]');
    passwordInput.type = 'text';
    if (toggle) {
        toggle.setAttribute('aria-pressed', 'true');
        toggle.setAttribute('aria-label', 'Hide password');
        const text = toggle.querySelector('[data-toggle-text]');
        if (text) {
            text.textContent = 'Hide';
        }
    }
}

/* -------------------------------------------------------------------------
 * Wire up the fields (strength, match and generate need no recovery session).
 * ---------------------------------------------------------------------- */
if (passwordInput && confirmInput) {
    passwordInput.addEventListener('input', () => {
        updateStrength();
        updateMatch();
    });
    confirmInput.addEventListener('input', updateMatch);

    const generateBtn = document.getElementById('generate-password');
    if (generateBtn) {
        generateBtn.addEventListener('click', () => {
            const password = generateStrongPassword(20);
            passwordInput.value = password;
            confirmInput.value = password;
            revealNewPassword();
            updateStrength();
            updateMatch();
            passwordInput.focus();
            passwordInput.select();
            setStatus('Generated a strong 20-character password and filled both fields. Copy it somewhere safe, then choose Update password.', 'info');
        });
    }
}

/* -------------------------------------------------------------------------
 * Complete the recovery with Supabase.
 * ---------------------------------------------------------------------- */
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
        // The reset is completed here in the browser — never let the form do a
        // native submit that could place the password in the URL.
        event.preventDefault();

        const password = passwordInput.value;
        const confirmation = confirmInput.value;

        if (password.length < 8) {
            setStatus('Use at least 8 characters.', 'error');
            passwordInput.focus();
            return;
        }
        if (password !== confirmation) {
            setStatus('The two passwords do not match.', 'error');
            updateMatch();
            confirmInput.focus();
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
