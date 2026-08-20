@php
    $sections = [
        ['label' => 'Perfil', 'icon' => 'user-circle', 'route' => 'profile.edit'],
        ['label' => 'Segurança', 'icon' => 'shield-check', 'route' => 'security.edit'],
    ];
@endphp

<div class="flex items-start gap-6 max-md:flex-col">
    <nav class="w-full shrink-0 md:w-[184px]" aria-label="Configurações">
        <div class="flex gap-1 md:flex-col">
            @foreach ($sections as $section)
                <flux:button
                    size="sm"
                    :href="route($section['route'])"
                    :icon="$section['icon']"
                    wire:navigate
                    class="justify-start! {{ request()->routeIs($section['route'])
                        ? 'border-transparent! bg-secondary! text-foreground!'
                        : 'border-transparent! bg-transparent! text-muted-foreground! hover:bg-white/5! hover:text-foreground!' }} w-full"
                >
                    {{ $section['label'] }}
                </flux:button>
            @endforeach
        </div>
    </nav>

    <div class="flex w-full min-w-0 max-w-2xl flex-col gap-4">
        {{ $slot }}
    </div>
</div>
