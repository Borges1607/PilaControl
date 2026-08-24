@props(['label' => null, 'size' => null])

<flux:input
    type="text"
    inputmode="numeric"
    placeholder="0,00"
    autocomplete="off"
    x-money-mask
    :label="$label"
    :size="$size"
    {{ $attributes->class(['font-mono!']) }}
/>
