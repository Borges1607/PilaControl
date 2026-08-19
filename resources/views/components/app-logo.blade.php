@props([
    'sidebar' => false,
])

@php
    $mark = 'flex aspect-square size-6 shrink-0 items-center justify-center rounded bg-accent text-xs font-bold text-accent-foreground';
@endphp

@if ($sidebar)
    <flux:sidebar.brand :name="config('app.name', 'Laravel')" {{ $attributes }}>
        <x-slot name="logo" class="{{ $mark }}">₢</x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand :name="config('app.name', 'Laravel')" {{ $attributes }}>
        <x-slot name="logo" class="{{ $mark }}">₢</x-slot>
    </flux:brand>
@endif
