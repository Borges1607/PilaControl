@php
    // Navegação do protótipo. `route` nulo = tela ainda não construída.
    $nav = [
        ['label' => 'Dashboard', 'icon' => 'squares-2x2', 'route' => 'dashboard'],
        ['label' => 'Transações', 'icon' => 'arrows-right-left', 'route' => 'transactions.index'],
        ['label' => 'Orçamento', 'icon' => 'rectangle-group', 'route' => 'budgets.index'],
        ['label' => 'Metas', 'icon' => 'flag', 'route' => 'goals.index'],
        ['label' => 'Relatórios', 'icon' => 'chart-bar', 'route' => null],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-background text-foreground">
        <flux:sidebar sticky collapsible="mobile" class="w-56! gap-0! border-e border-border bg-card p-0!">
            <flux:sidebar.header class="h-14 shrink-0 border-b border-border px-5">
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav class="p-3">
                @foreach ($nav as $item)
                    @if ($item['route'])
                        <flux:sidebar.item
                            :icon="$item['icon']"
                            :href="route($item['route'])"
                            :current="request()->routeIs($item['route'])"
                            wire:navigate
                            class="text-muted-foreground! data-current:bg-secondary! data-current:text-foreground! data-current:border-transparent! hover:bg-white/5! hover:text-foreground!"
                        >
                            {{ $item['label'] }}
                        </flux:sidebar.item>
                    @else
                        <flux:tooltip content="Em breve" position="right">
                            <div
                                class="flex h-8 w-full cursor-not-allowed items-center gap-3 rounded-lg px-3 text-sm font-medium text-muted-foreground/60"
                                aria-disabled="true"
                            >
                                <flux:icon :icon="$item['icon']" class="size-4 shrink-0" />
                                {{ $item['label'] }}
                            </div>
                        </flux:tooltip>
                    @endif
                @endforeach
            </flux:sidebar.nav>

            <flux:spacer />

            <div class="flex shrink-0 flex-col gap-2 border-t border-border p-4">
                <flux:button
                    :href="route('transactions.index')"
                    wire:navigate
                    variant="primary"
                    size="sm"
                    icon="plus"
                    class="w-full font-semibold!"
                >
                    Nova Transação
                </flux:button>

                <flux:tooltip content="Em breve" position="top">
                    <flux:button
                        size="sm"
                        icon="tag"
                        disabled
                        class="w-full border-border! bg-transparent! text-muted-foreground!"
                    >
                        Categorias
                    </flux:button>
                </flux:tooltip>
            </div>
        </flux:sidebar>

        <flux:header sticky class="h-14 border-b border-border bg-card px-5!">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <h1 class="text-sm font-semibold tracking-tight">
                {{ $title ?? config('app.name') }}
            </h1>

            <flux:spacer />

            <span class="hidden font-mono text-xs text-muted-foreground sm:block">
                {{ \App\Support\MonthLabel::weekdayDate(now()) }}
            </span>

            <div class="ms-3 flex items-center gap-2 border-s border-border ps-3">
                <flux:dropdown position="bottom" align="end">
                    <button type="button" class="flex items-center gap-2" data-test="user-menu-button">
                        <flux:avatar
                            size="xs"
                            circle
                            :name="auth()->user()->name"
                            initials:single
                            class="bg-accent! font-bold! text-background!"
                        />

                        <span class="hidden text-xs font-medium md:block">
                            {{ str(auth()->user()->name)->before(' ') }}
                        </span>
                    </button>

                    <flux:menu>
                        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                            <flux:avatar :name="auth()->user()->name" :initials="auth()->user()->initials()" />

                            <div class="grid flex-1 text-start text-sm leading-tight">
                                <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                            </div>
                        </div>

                        <flux:menu.separator />

                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu>
                </flux:dropdown>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <flux:button
                        type="submit"
                        size="xs"
                        variant="ghost"
                        class="text-muted-foreground!"
                        data-test="logout-button"
                    >
                        Sair
                    </flux:button>
                </form>
            </div>
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
