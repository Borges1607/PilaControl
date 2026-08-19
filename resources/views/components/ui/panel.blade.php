@props([
    'heading' => null,
    'flush' => false,
])

<flux:card
    {{ $attributes->class(['bg-card! border-border! p-0! overflow-hidden']) }}
>
    @if ($heading || isset($actions))
        <div class="flex items-center justify-between gap-4 border-b border-border px-4 py-3">
            @if ($heading)
                <h3 class="text-xs font-semibold tracking-widest text-muted-foreground uppercase">
                    {{ $heading }}
                </h3>
            @endif

            {{ $actions ?? '' }}
        </div>
    @endif

    <div @class(['p-4' => ! $flush])>
        {{ $slot }}
    </div>
</flux:card>
