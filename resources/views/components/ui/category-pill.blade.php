@props([
    'category',
])

{{-- A cor vem do registro da categoria, não da paleta do tema. --}}
<span
    class="inline-block rounded-sm px-1.5 py-0.5 font-mono text-[10px] font-medium whitespace-nowrap"
    style="background-color: {{ $category->color }}22; color: {{ $category->color }}"
>
    {{ $category->name }}
</span>
