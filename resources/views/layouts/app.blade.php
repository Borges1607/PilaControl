<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main class="p-5!">
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
