{{--
    In-platform confirmation modal (replaces the native browser confirm() dialog).
    Any <form data-confirm="Message?"> is intercepted and this DS-styled modal is
    shown instead. Mounted once in the app layout.
--}}
<div id="ds-confirm" class="hidden fixed inset-0 z-50 items-center justify-center bg-slatecard/50 p-4" role="dialog" aria-modal="true" aria-labelledby="ds-confirm-title">
    <div class="w-full max-w-sm rounded-panel border border-line bg-surface p-6 shadow-panel">
        <h2 id="ds-confirm-title" class="text-lg font-black text-slatecard">Please confirm</h2>
        <p data-confirm-message class="mt-2 text-sm text-ink-soft">Are you sure?</p>
        <div class="mt-5 flex justify-end gap-3">
            <button type="button" data-confirm-cancel class="rounded-control px-4 py-2 text-sm font-semibold text-ink-soft transition hover:text-slatecard focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq">Cancel</button>
            <button type="button" data-confirm-ok class="rounded-control bg-button-primary px-4 py-2 text-sm font-semibold text-button-primary-text transition hover:bg-button-primary-hover focus:outline-none focus:ring-2 focus:ring-button-primary focus:ring-offset-2">Confirm</button>
        </div>
    </div>
</div>
<script>
    (function () {
        var overlay = document.getElementById('ds-confirm');
        if (!overlay) return;
        var msg = overlay.querySelector('[data-confirm-message]');
        var okBtn = overlay.querySelector('[data-confirm-ok]');
        var pending = null;
        function open(form) {
            pending = form;
            msg.textContent = form.getAttribute('data-confirm') || 'Are you sure?';
            overlay.classList.remove('hidden');
            overlay.classList.add('flex');
            okBtn.focus();
        }
        function close() {
            overlay.classList.add('hidden');
            overlay.classList.remove('flex');
            pending = null;
        }
        document.addEventListener('submit', function (e) {
            var f = e.target;
            if (f && f.matches && f.matches('[data-confirm]') && !f.dataset.confirmed) {
                e.preventDefault();
                open(f);
            }
        }, true);
        okBtn.addEventListener('click', function () {
            if (!pending) return;
            var f = pending;
            f.dataset.confirmed = '1';
            close();
            f.submit();
        });
        overlay.querySelectorAll('[data-confirm-cancel]').forEach(function (el) { el.addEventListener('click', close); });
        overlay.addEventListener('click', function (e) { if (e.target === overlay) close(); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !overlay.classList.contains('hidden')) close(); });
    })();
</script>
