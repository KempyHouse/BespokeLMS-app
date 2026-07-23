@extends('layouts.app')

@section('title', 'My profile')

@section('content')
{{-- Cropper.js (image crop/zoom) from cdnjs. --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" onerror="this.remove()">

<div class="mx-auto max-w-3xl">
    <a href="{{ route('dashboard') }}"
       class="inline-flex items-center gap-1.5 text-xs font-semibold text-teachhq transition hover:text-teachhq-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq focus-visible:ring-offset-2">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
        Back to dashboard
    </a>
    <h1 class="mt-3 text-2xl font-black text-slatecard">My profile</h1>
    <p class="mt-1 text-sm text-ink-soft">Update your name, role title and photo. These appear across BespokeLMS.</p>

    @if (session('status'))
        <div role="status" class="mt-5 flex items-start gap-3 rounded-panel border border-rag-green/40 bg-rag-green-soft p-4 text-sm text-rag-green">
            <svg class="mt-0.5 h-icon w-icon flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
            <p>{{ session('status') }}</p>
        </div>
    @endif
    @if (session('profileError'))
        <div role="alert" class="mt-5 flex items-start gap-3 rounded-panel border border-rag-red/40 bg-rag-red-soft p-4 text-sm text-rag-red">
            <svg class="mt-0.5 h-icon w-icon flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
            <p>{{ session('profileError') }}</p>
        </div>
    @endif
    @if ($errors->any())
        <div role="alert" class="mt-5 rounded-panel border border-rag-red/40 bg-rag-red-soft p-4 text-sm text-rag-red">
            <ul class="list-inside list-disc space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Photo --}}
    <section class="mt-6 rounded-panel border border-line bg-surface p-6 shadow-panel">
        <h2 class="text-lg font-black text-slatecard">Photo</h2>
        <p class="mt-1 text-caption text-ink-soft">A square image works best. You can zoom and crop before saving.</p>

        <div class="mt-5 flex flex-col items-start gap-5 sm:flex-row sm:items-center">
            <div class="flex-none">
                @if ($user->avatarUrl())
                    <img src="{{ $user->avatarUrl() }}" alt="Your profile photo" class="h-24 w-24 rounded-full border border-line object-cover shadow-quiet">
                @else
                    <span class="flex h-24 w-24 items-center justify-center rounded-full bg-teachhq text-2xl font-black text-on-brand shadow-quiet">{{ $user->initials() }}</span>
                @endif
            </div>
            <div class="flex flex-col gap-2">
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" data-avatar-pick
                            class="inline-flex items-center gap-1.5 rounded-control bg-button-primary px-4 py-2 text-sm font-semibold text-button-primary-text transition hover:bg-button-primary-hover focus:outline-none focus:ring-2 focus:ring-button-primary focus:ring-offset-2">
                        <svg class="h-icon w-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>
                        {{ $user->avatarUrl() ? 'Change photo' : 'Upload photo' }}
                    </button>
                    @if ($user->avatarUrl())
                        <form method="POST" action="{{ route('profile.avatar.remove') }}" onsubmit="return confirm('Remove your profile photo?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="inline-flex items-center gap-1.5 rounded-control border border-line bg-surface px-4 py-2 text-sm font-semibold text-rag-red transition hover:bg-rag-red-soft focus:outline-none focus:ring-2 focus:ring-rag-red focus:ring-offset-2">
                                Remove
                            </button>
                        </form>
                    @endif
                </div>
                <p class="text-micro text-ink-faint">PNG, JPG or WebP, up to 5&nbsp;MB.</p>
            </div>
        </div>

        <input type="file" accept="image/png,image/jpeg,image/webp" data-avatar-input class="hidden">
    </section>

    {{-- Details --}}
    <section class="mt-6 rounded-panel border border-line bg-surface p-6 shadow-panel">
        <h2 class="text-lg font-black text-slatecard">Details</h2>
        <form method="POST" action="{{ route('profile.update') }}" class="mt-5">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="first_name" class="block text-sm font-semibold text-slatecard">First name</label>
                    <input type="text" id="first_name" name="first_name" required autocomplete="given-name"
                           value="{{ old('first_name', $user->firstName ?? '') }}"
                           class="mt-1.5 w-full rounded-control border border-line bg-paper px-3 py-2 text-sm text-slatecard focus:outline-none focus:ring-2 focus:ring-button-primary">
                </div>
                <div>
                    <label for="last_name" class="block text-sm font-semibold text-slatecard">Surname</label>
                    <input type="text" id="last_name" name="last_name" autocomplete="family-name"
                           value="{{ old('last_name', $user->lastName ?? '') }}"
                           class="mt-1.5 w-full rounded-control border border-line bg-paper px-3 py-2 text-sm text-slatecard focus:outline-none focus:ring-2 focus:ring-button-primary">
                </div>
                <div class="sm:col-span-2">
                    <label for="job_title" class="block text-sm font-semibold text-slatecard">Job title</label>
                    <input type="text" id="job_title" name="job_title" autocomplete="organization-title"
                           value="{{ old('job_title', $user->jobTitle ?? '') }}" placeholder="e.g. Compliance Manager"
                           class="mt-1.5 w-full rounded-control border border-line bg-paper px-3 py-2 text-sm text-slatecard placeholder:text-ink-faint focus:outline-none focus:ring-2 focus:ring-button-primary">
                </div>
                <div class="sm:col-span-2">
                    <label for="email" class="block text-sm font-semibold text-slatecard">Email</label>
                    <input type="email" id="email" value="{{ $user->email }}" disabled
                           class="mt-1.5 w-full cursor-not-allowed rounded-control border border-line bg-line-soft px-3 py-2 text-sm text-ink-soft">
                    <p class="mt-1 text-micro text-ink-faint">Your sign-in email is managed by your administrator.</p>
                </div>
            </div>
            <div class="mt-5 flex items-center gap-3">
                <button type="submit"
                        class="inline-flex items-center gap-1.5 rounded-control bg-button-primary px-4 py-2 text-sm font-semibold text-button-primary-text transition hover:bg-button-primary-hover focus:outline-none focus:ring-2 focus:ring-button-primary focus:ring-offset-2">
                    Save changes
                </button>
            </div>
        </form>
    </section>

    {{-- Preferences --}}
    <section id="preferences" class="mt-6 scroll-mt-24 rounded-panel border border-line bg-surface p-6 shadow-panel">
        <h2 class="text-lg font-black text-slatecard">Preferences</h2>
        <p class="mt-1 text-caption text-ink-soft">How BespokeLMS looks for you. Applies instantly and is saved to your profile.</p>

        <div class="mt-5" data-theme-endpoint="{{ route('preferences.theme') }}">
            <span class="block text-sm font-semibold text-slatecard">Theme</span>
            <p class="mt-0.5 text-mini text-ink-soft">"System" follows your device's light or dark setting.</p>
            <div class="mt-2 grid max-w-md grid-cols-3 gap-1.5 rounded-control border border-line bg-paper p-1.5" role="group" aria-label="Theme preference">
                <button type="button" data-theme-set="light" class="theme-opt rounded-lg py-2 text-sm font-semibold text-ink-soft transition focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq">Light</button>
                <button type="button" data-theme-set="dark" class="theme-opt rounded-lg py-2 text-sm font-semibold text-ink-soft transition focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq">Dark</button>
                <button type="button" data-theme-set="system" class="theme-opt rounded-lg py-2 text-sm font-semibold text-ink-soft transition focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq">System</button>
            </div>
        </div>
    </section>
</div>

{{-- Crop modal --}}
<div data-crop-modal class="fixed inset-0 z-50 hidden items-center justify-center bg-scrim p-4">
    <div class="flex w-full max-w-lg flex-col rounded-panel border border-line bg-surface shadow-drawer">
        <div class="flex items-center justify-between border-b border-line px-5 py-3">
            <h3 class="text-base font-black text-slatecard">Adjust your photo</h3>
            <button type="button" data-crop-cancel aria-label="Cancel"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-control text-ink-faint transition hover:bg-paper hover:text-slatecard focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq">
                <svg class="h-icon w-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>
        <div class="p-5">
            <div class="mx-auto max-h-80 w-full overflow-hidden rounded-control bg-paper">
                <img data-crop-image alt="" class="block max-w-full">
            </div>
            <div class="mt-3 flex items-center justify-center gap-2">
                <button type="button" data-crop-zoom-out aria-label="Zoom out"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-control border border-line bg-surface text-slatecard transition hover:bg-paper focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq">
                    <svg class="h-icon w-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/><path d="M8 11h6"/></svg>
                </button>
                <button type="button" data-crop-zoom-in aria-label="Zoom in"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-control border border-line bg-surface text-slatecard transition hover:bg-paper focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq">
                    <svg class="h-icon w-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/><path d="M11 8v6"/><path d="M8 11h6"/></svg>
                </button>
            </div>
        </div>
        <div class="flex items-center justify-end gap-3 border-t border-line px-5 py-3">
            <button type="button" data-crop-cancel
                    class="rounded-control border border-line bg-surface px-4 py-2 text-sm font-semibold text-slatecard transition hover:bg-paper focus:outline-none focus:ring-2 focus:ring-teachhq focus:ring-offset-2">
                Cancel
            </button>
            <button type="button" data-crop-apply
                    class="inline-flex items-center gap-1.5 rounded-control bg-button-primary px-4 py-2 text-sm font-semibold text-button-primary-text transition hover:bg-button-primary-hover focus:outline-none focus:ring-2 focus:ring-button-primary focus:ring-offset-2 disabled:opacity-60">
                <span data-crop-apply-label>Save photo</span>
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js" crossorigin="anonymous" referrerpolicy="no-referrer" onerror="window.__cropperFailed=true"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var pickBtn = document.querySelector('[data-avatar-pick]');
    var input = document.querySelector('[data-avatar-input]');
    var modal = document.querySelector('[data-crop-modal]');
    var image = document.querySelector('[data-crop-image]');
    var applyBtn = document.querySelector('[data-crop-apply]');
    var applyLabel = document.querySelector('[data-crop-apply-label]');
    var cancelBtns = document.querySelectorAll('[data-crop-cancel]');
    var zoomIn = document.querySelector('[data-crop-zoom-in]');
    var zoomOut = document.querySelector('[data-crop-zoom-out]');
    if (!pickBtn || !input || !modal || !image) return;

    var endpoint = @json(route('profile.avatar'));
    var tokenMeta = document.querySelector('meta[name=csrf-token]');
    var csrf = tokenMeta ? tokenMeta.getAttribute('content') : '';
    var cropper = null, objectUrl = null;

    function openModal() { modal.classList.remove('hidden'); modal.classList.add('flex'); }
    function closeModal() {
        modal.classList.add('hidden'); modal.classList.remove('flex');
        if (cropper) { cropper.destroy(); cropper = null; }
        if (objectUrl) { URL.revokeObjectURL(objectUrl); objectUrl = null; }
        input.value = '';
    }

    pickBtn.addEventListener('click', function () {
        if (window.__cropperFailed || typeof window.Cropper === 'undefined') {
            // Fallback: no cropper available — upload the raw file directly.
            input.removeAttribute('data-crop');
            input.click();
            return;
        }
        input.setAttribute('data-crop', '1');
        input.click();
    });

    input.addEventListener('change', function () {
        var file = input.files && input.files[0];
        if (!file) return;

        // Fallback path: upload as-is when Cropper is unavailable.
        if (input.getAttribute('data-crop') !== '1' || typeof window.Cropper === 'undefined') {
            var fd0 = new FormData();
            fd0.append('avatar', file, file.name || 'avatar');
            upload(fd0);
            return;
        }

        objectUrl = URL.createObjectURL(file);
        image.src = objectUrl;
        openModal();
        if (cropper) { cropper.destroy(); cropper = null; }
        cropper = new window.Cropper(image, {
            aspectRatio: 1, viewMode: 1, autoCropArea: 1, background: false,
            dragMode: 'move', guides: false, movable: true, zoomable: true, responsive: true
        });
    });

    if (zoomIn) zoomIn.addEventListener('click', function () { if (cropper) cropper.zoom(0.1); });
    if (zoomOut) zoomOut.addEventListener('click', function () { if (cropper) cropper.zoom(-0.1); });
    cancelBtns.forEach(function (b) { b.addEventListener('click', closeModal); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal(); });

    if (applyBtn) applyBtn.addEventListener('click', function () {
        if (!cropper) return;
        applyBtn.disabled = true;
        if (applyLabel) applyLabel.textContent = 'Saving…';
        cropper.getCroppedCanvas({ width: 512, height: 512, imageSmoothingQuality: 'high' }).toBlob(function (blob) {
            var fd = new FormData();
            fd.append('avatar', blob, 'avatar.png');
            upload(fd);
        }, 'image/png', 0.92);
    });

    function upload(fd) {
        fd.append('_token', csrf);
        fetch(endpoint, { method: 'POST', body: fd, headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (r) {
                if (r.ok || r.status === 302) { window.location.reload(); return; }
                throw new Error('Upload failed (' + r.status + ')');
            })
            .catch(function () {
                if (applyBtn) applyBtn.disabled = false;
                if (applyLabel) applyLabel.textContent = 'Save photo';
                alert('Sorry, your photo could not be uploaded. Please try again.');
            });
    }
});
</script>
@endpush
@endsection
