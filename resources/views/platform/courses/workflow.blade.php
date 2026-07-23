@extends('layouts.app')

@section('title', 'Workflow · Global Courses')

@php
    $c = $course;
    $currentStateId = $current['state_id'] ?? null;
    $currentState = null;
    foreach ($states as $s) { if (($s['id'] ?? null) === $currentStateId) { $currentState = $s; break; } }

    $actionLabels = [
        'submit' => 'Submit for review',
        'approve' => 'Approve',
        'reapprove' => 'Re-approve',
        'request_changes' => 'Request changes',
        'publish' => 'Publish',
        'mark_review_due' => 'Mark review due',
        'retire' => 'Retire',
        'reject' => 'Reject',
    ];
    $roleLabels = ['author' => 'Author', 'reviewer' => 'Reviewer', 'approver' => 'Approver'];
    $pname = function ($p) {
        if (!is_array($p)) return null;
        $n = trim((string) ($p['full_name'] ?? ''));
        if ($n === '') $n = trim(((string) ($p['first_name'] ?? '')).' '.((string) ($p['last_name'] ?? '')));
        return $n !== '' ? $n : null;
    };
@endphp

@section('content')
<div class="flex flex-col items-start gap-6 lg:flex-row lg:items-start lg:gap-8">
    <aside class="w-full lg:w-rail lg:flex-none lg:sticky lg:top-20 lg:self-start">
        <x-workspace-switcher active="platform" />
        <div class="rounded-control bg-paper p-6">
            <a href="{{ route('platform.courses.show', $c['id']) }}"
               class="inline-flex items-center gap-1.5 text-xs font-semibold text-teachhq transition hover:text-teachhq-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-teachhq focus-visible:ring-offset-2">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                Back to course
            </a>
            <p class="mt-3 text-xs font-bold uppercase tracking-wider text-teachhq">Course editor</p>
            <h1 class="mt-1 text-xl font-black text-slatecard">Workflow &amp; approval</h1>
            <p class="mt-2 text-caption text-ink-soft">Move the latest version through review and sign-off. Approval requires a different person from the one who submitted.</p>
        </div>
    </aside>

    <main class="min-w-0 flex-1">
        <div class="mb-6">
            <p class="text-xs font-bold uppercase tracking-wider text-teachhq">BespokeLMS &middot; Global catalogue</p>
            <h2 class="mt-1 text-2xl font-black text-slatecard">{{ $c['title'] ?? 'Course' }} — workflow</h2>
        </div>

        @if (session('status'))
            <div role="status" class="mb-5 rounded-panel border border-rag-green/40 bg-rag-green-soft p-4 text-sm text-rag-green">{{ session('status') }}</div>
        @endif
        @if (session('editorError'))
            <div role="alert" class="mb-5 rounded-panel border border-rag-red/40 bg-rag-red-soft p-4 text-sm text-rag-red">{{ session('editorError') }}</div>
        @endif

        @if ($version === null)
            <p class="rounded-panel border border-dashed border-line bg-paper p-4 text-sm text-ink-soft">This course has no version yet. Create one in the Content builder first.</p>
        @else
            {{-- State pipeline --}}
            <section class="mb-5 rounded-panel border border-line bg-surface p-6 shadow-panel">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h3 class="text-lg font-black text-slatecard">Current state</h3>
                    <span class="text-mini text-ink-faint">Version {{ $version['semver'] ?? '' }} (v{{ $version['version_no'] ?? '' }})</span>
                </div>
                <div class="mt-4 flex flex-wrap items-center gap-1.5">
                    @foreach ($states as $s)
                        @php $isCur = ($s['id'] ?? null) === $currentStateId; @endphp
                        @if (!$loop->first)<span class="text-ink-faint">→</span>@endif
                        <span class="rounded-control px-2.5 py-1 text-xs font-semibold {{ $isCur ? 'bg-teachhq text-white' : 'border border-line text-ink-soft' }}">{{ $s['label'] ?? $s['key'] }}</span>
                    @endforeach
                </div>
            </section>

            {{-- Actions --}}
            <section class="mb-5 rounded-panel border border-line bg-surface p-6 shadow-panel">
                <h3 class="text-lg font-black text-slatecard">Actions</h3>
                @if (empty($transitions))
                    <p class="mt-2 text-sm text-ink-soft">No actions are available from <strong>{{ $currentState['label'] ?? 'the current state' }}</strong>.</p>
                @else
                    <p class="mt-1 text-caption text-ink-soft">Add an optional note, then choose an action.</p>
                    <form method="POST" action="{{ route('platform.courses.workflow.transition', $c['id']) }}" class="mt-4">
                        @csrf
                        <textarea name="comment" rows="2" placeholder="Optional note (shown in history)" class="w-full rounded-control border border-line bg-paper px-3 py-2 text-sm text-slatecard placeholder:text-ink-faint focus:outline-none focus:ring-2 focus:ring-button-primary"></textarea>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach ($transitions as $t)
                                <button type="submit" name="action" value="{{ $t['action'] }}"
                                    class="inline-flex flex-col items-start rounded-control bg-button-primary px-4 py-2 text-sm font-semibold text-button-primary-text transition hover:bg-button-primary-hover focus:outline-none focus:ring-2 focus:ring-button-primary focus:ring-offset-2">
                                    <span>{{ $actionLabels[$t['action']] ?? $t['action'] }} → {{ $t['to_label'] }}</span>
                                    @if (!empty($t['requires_distinct_actor']))<span class="text-micro font-normal opacity-90">needs a different person</span>@endif
                                </button>
                            @endforeach
                        </div>
                    </form>
                @endif
            </section>

            {{-- Assignments (read-only) --}}
            <section class="mb-5 rounded-panel border border-line bg-surface p-6 shadow-panel">
                <h3 class="text-lg font-black text-slatecard">Assigned people</h3>
                @if (empty($assignments))
                    <p class="mt-2 text-sm text-ink-soft">No author, reviewer or approver assigned yet. Assignment editing arrives in a later slice.</p>
                @else
                    <dl class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-3">
                        @foreach ($assignments as $a)
                            <div class="rounded-control border border-line bg-paper p-3">
                                <dt class="text-micro font-bold uppercase tracking-wide text-ink-faint">{{ $roleLabels[$a['role']] ?? $a['role'] }}</dt>
                                <dd class="mt-0.5 text-sm font-semibold text-slatecard">{{ $pname($a['profiles'] ?? null) ?? 'Unknown' }}</dd>
                            </div>
                        @endforeach
                    </dl>
                @endif
            </section>

            {{-- History --}}
            <section class="rounded-panel border border-line bg-surface p-6 shadow-panel">
                <h3 class="text-lg font-black text-slatecard">History</h3>
                @if (empty($history))
                    <p class="mt-2 text-sm text-ink-soft">No transitions recorded yet.</p>
                @else
                    <ol class="mt-3 space-y-2">
                        @foreach ($history as $h)
                            <li class="flex flex-wrap items-baseline gap-x-2 gap-y-0.5 border-b border-line pb-2 text-sm">
                                <span class="font-semibold text-slatecard">{{ $actionLabels[$h['action']] ?? $h['action'] }}</span>
                                <span class="text-ink-soft">{{ $h['from_label'] }} → {{ $h['to_label'] }}</span>
                                @if ($pn = $pname($h['profiles'] ?? null))<span class="text-mini text-ink-faint">by {{ $pn }}</span>@endif
                                <span class="ml-auto text-mini text-ink-faint">{{ isset($h['at']) ? \Illuminate\Support\Str::of((string) $h['at'])->replace('T', ' ')->limit(16, '') : '' }}</span>
                                @if (!empty($h['comment']))<span class="w-full text-mini text-ink-soft">“{{ $h['comment'] }}”</span>@endif
                            </li>
                        @endforeach
                    </ol>
                @endif
            </section>
        @endif
    </main>
</div>
@endsection
