@extends('layouts.app')

@section('title', 'Team')

@section('content')
<div class="flex flex-col items-start gap-6 lg:flex-row lg:items-stretch lg:gap-8">
    {{-- Left rail: header comes from the layout; the rail holds only the
         workspace switcher for now. The Team workspace UI is migrated in here. --}}
    <aside class="w-full lg:w-rail lg:flex-none lg:sticky lg:top-24 lg:self-start" aria-label="Team workspace navigation">
        <x-workspace-switcher active="team" />
    </aside>

    {{-- Blank content region — to be built. --}}
    <div class="min-w-0 flex-1"></div>
</div>
@endsection
