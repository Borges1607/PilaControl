@props([
    'name' => null,
    'type' => 'bar',
    'labels' => [],
    'series' => [],
    'height' => 200,
    'money' => true,
])

{{--
    Única ponte entre as telas e o Chart.js. `wire:ignore` é obrigatório: o Livewire
    não pode re-renderizar por cima do DOM que a biblioteca controla.

    Por isso, gráfico que muda de dados sem recarregar a página precisa de `name`: é
    por ele que o `$this->dispatch('chart:data', name: ...)` do componente encontra
    este canvas. Sem `name`, o gráfico é estático depois de montado.
--}}
<div
    wire:ignore
    x-data="chart(@js(['name' => $name, 'type' => $type, 'labels' => $labels, 'series' => $series, 'money' => $money]))"
    {{ $attributes->class(['relative w-full']) }}
    style="height: {{ $height }}px"
>
    <canvas x-ref="canvas"></canvas>
</div>
