@props([
    'type' => 'bar',
    'labels' => [],
    'series' => [],
    'height' => 200,
    'money' => true,
])

{{--
    Única ponte entre as telas e o Chart.js. `wire:ignore` é obrigatório: o Livewire
    não pode re-renderizar por cima do DOM que a biblioteca controla.
--}}
<div
    wire:ignore
    x-data="chart(@js(['type' => $type, 'labels' => $labels, 'series' => $series, 'money' => $money]))"
    {{ $attributes->class(['relative w-full']) }}
    style="height: {{ $height }}px"
>
    <canvas x-ref="canvas"></canvas>
</div>
