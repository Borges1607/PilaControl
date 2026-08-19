@props([
    'label',
    'value',
    'sub' => null,
    'tone' => 'neutral',
    'icon' => null,
])

@php
    $toneClass = match ($tone) {
        'income' => 'text-income',
        'expense' => 'text-expense',
        default => 'text-foreground',
    };
@endphp

<flux:card {{ $attributes->class(['flex flex-col gap-2 bg-card! border-border! p-4!']) }}>
    <div class="flex items-center justify-between gap-2">
        <span class="text-xs font-medium tracking-widest text-muted-foreground uppercase">
            {{ $label }}
        </span>

        @if ($icon)
            <flux:icon :icon="$icon" variant="mini" class="size-4 shrink-0 text-muted-foreground" />
        @endif
    </div>

    <span class="font-mono text-2xl leading-none font-semibold {{ $toneClass }}">
        {{ $value }}
    </span>

    @if ($sub)
        <span class="text-xs text-muted-foreground">{{ $sub }}</span>
    @endif
</flux:card>
