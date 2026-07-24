{{-- Course Edit Form Footer: Status Action Buttons --}}
{{-- Replaces catalog_status dropdown with intelligent action buttons --}}

<div class="mb-6 rounded-panel border border-line bg-surface p-6 shadow-panel">
    <h3 class="mb-4 text-lg font-black text-slatecard">Course Status</h3>

    {{-- Current Status Badge (Read-only) --}}
    <div class="mb-4 flex items-center gap-3">
        <span class="text-sm font-semibold text-ink-soft">Current status:</span>
        <span class="rounded-control {{ $statusBadgeClass }} px-3 py-1 text-sm font-semibold">
            {{ $statusLabel }}
        </span>
    </div>

    {{-- Status Context (if in workflow) --}}
    @if ($courseHasApprovalWorkflow)
        <p class="mb-4 text-sm text-ink-soft">
            This course is under the editorial workflow.
            <strong>Publish</strong> will move it through review and approval before going live.
        </p>
    @else
        <p class="mb-4 text-sm text-ink-soft">
            This course publishes immediately when you click Publish.
        </p>
    @endif

    {{-- Readiness Checks --}}
    @if (!empty($readinessChecks))
        <div class="mb-4 space-y-2 rounded-control border border-line bg-paper p-3">
            <p class="text-sm font-semibold text-ink-faint">Before you can publish:</p>
            <ul class="space-y-1">
                @foreach ($readinessChecks as $check)
                    <li class="flex items-center gap-2 text-sm {{ $check['met'] ? 'text-ink-soft' : 'text-rag-red' }}">
                        @if ($check['met'])
                            <svg class="h-4 w-4 text-rag-green" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/></svg>
                        @else
                            <svg class="h-4 w-4 text-rag-red" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                        @endif
                        {{ $check['label'] }}
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Action Buttons --}}
    <div class="flex flex-wrap gap-3">
        {{-- Save as Draft --}}
        <form method="POST" action="{{ route('platform.courses.update', $course['id']) }}" class="inline">
            @csrf
            @method('PUT')
            {{-- All course fields --}}
            @include('platform.courses.partials.course-fields')

            <input type="hidden" name="action" value="save_draft">
            <button type="submit" class="rounded-control border border-line px-4 py-2 text-sm font-semibold text-slatecard transition hover:bg-surface focus:outline-none focus:ring-2 focus:ring-teachhq focus:ring-offset-2">
                Save as Draft
            </button>
        </form>

        {{-- Save & Publish --}}
        <form method="POST" action="{{ route('platform.courses.update', $course['id']) }}" class="inline">
            @csrf
            @method('PUT')
            {{-- All course fields --}}
            @include('platform.courses.partials.course-fields')

            <input type="hidden" name="action" value="publish">
            <button type="submit"
                {{ $publishDisabled ? 'disabled' : '' }}
                class="rounded-control bg-button-primary px-4 py-2 text-sm font-semibold text-button-primary-text transition {{ $publishDisabled ? 'opacity-50 cursor-not-allowed' : 'hover:bg-button-primary-hover' }} focus:outline-none focus:ring-2 focus:ring-button-primary focus:ring-offset-2"
                @if ($publishDisabled) title="{{ $publishDisabledReason }}" @endif>
                Save & Publish
            </button>
            @if ($publishDisabled)
                <span class="ml-2 text-sm text-rag-red">{{ $publishDisabledReason }}</span>
            @endif
        </form>
    </div>
</div>
