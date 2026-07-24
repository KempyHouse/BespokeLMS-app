@extends('layouts.app')

@section('title', 'Team')

@section('content')
<div class="flex flex-col items-start gap-6 lg:flex-row lg:items-start lg:gap-8">
    {{-- Left rail: Team workspace navigation with switcher and menu items. --}}
    <x-team-nav active="team-members" />

    {{-- Blank content region — to be built. --}}
    <div class="min-w-0 flex-1"></div>
</div>
@endsection
