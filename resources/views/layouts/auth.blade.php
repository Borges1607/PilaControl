@props([
    'title' => null,
    'subtitle' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-background text-foreground antialiased">
        <div class="relative flex min-h-svh items-center justify-center overflow-hidden p-4">
            {{--
                Fundo do protótipo: malha de pontos azul sobre o fundo escuro, com um
                brilho verde difuso atrás do cartão. Os dois gradientes ficam em `style`
                porque são desenho, não cor de tema — nenhum utilitário do Tailwind os cobre.
            --}}
            <div
                aria-hidden="true"
                class="pointer-events-none absolute inset-0"
                style="
                    background-image: radial-gradient(circle at center, rgb(56 139 253 / 0.12) 1px, transparent 1px);
                    background-size: 24px 24px;
                "
            ></div>

            <div
                aria-hidden="true"
                class="pointer-events-none absolute top-[20%] left-1/2 size-100 -translate-x-1/2 rounded-full"
                style="background: radial-gradient(circle, rgb(0 230 118 / 0.06) 0%, transparent 70%)"
            ></div>

            <div class="relative z-10 flex w-full max-w-sm flex-col gap-6" data-auth>
                <div class="flex flex-col items-center gap-3">
                    <a href="{{ route('home') }}" wire:navigate class="flex flex-col items-center gap-3">
                        <span class="flex size-12 items-center justify-center rounded-xl bg-accent text-2xl font-bold text-accent-foreground">
                            ₢
                        </span>

                        <span class="sr-only">{{ config('app.name', 'Laravel') }}</span>
                    </a>

                    <div class="text-center">
                        <h1 class="text-xl font-bold tracking-tight">{{ config('app.name', 'Laravel') }}</h1>

                        @if ($subtitle)
                            <p class="mt-0.5 text-xs text-muted-foreground">{{ $subtitle }}</p>
                        @endif
                    </div>
                </div>

                {{ $slot }}
            </div>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
