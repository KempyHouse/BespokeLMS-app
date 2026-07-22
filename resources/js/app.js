import '@fontsource/lato/400.css';
import '@fontsource/lato/700.css';
import '@fontsource/lato/900.css';

/**
 * Progressive enhancement: show / hide password toggles.
 * A button with data-toggle-password="<input id>" flips that input's type.
 * Works without JS (the field is a normal password input by default).
 */
document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-toggle-password]');
    if (!button) {
        return;
    }

    const input = document.getElementById(button.getAttribute('data-toggle-password'));
    if (!input) {
        return;
    }

    const reveal = input.type === 'password';
    input.type = reveal ? 'text' : 'password';
    button.setAttribute('aria-pressed', String(reveal));
    button.setAttribute('aria-label', reveal ? 'Hide password' : 'Show password');

    const text = button.querySelector('[data-toggle-text]');
    if (text) {
        text.textContent = reveal ? 'Hide' : 'Show';
    }
});
