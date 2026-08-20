@props([
    'variant' => 'error',
])

@php
    // Fundo a ~7% e borda a ~27% da própria cor, como no protótipo (`{cor}11` / `{cor}44`).
    $tone = match ($variant) {
        'success' => 'border-income/27 bg-income/7 text-income',
        'info' => 'border-info/27 bg-info/7 text-info',
        default => 'border-expense/27 bg-expense/7 text-expense',
    };
@endphp

<div role="alert" {{ $attributes->class(['rounded border px-3 py-2.5 text-xs', $tone]) }}>
    {{ $slot }}
</div>
