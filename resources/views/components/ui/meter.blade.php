@props([
    'percent' => 0,
    'color' => null,
    'height' => 'h-1',
])

{{--
    Barra de progresso com cor por instância (cor da categoria, ou `expense` quando
    o limite estoura). O `flux:progress` só aceita cores nomeadas do próprio Flux,
    por isso a barra é nossa.
--}}
<div class="{{ $height }} w-full overflow-hidden rounded-full bg-secondary">
    <div
        class="h-full rounded-full transition-[width] duration-300 ease-out"
        style="width: {{ max(0, min(100, (float) $percent)) }}%; background-color: {{ $color ?? 'var(--color-info)' }}"
    ></div>
</div>
