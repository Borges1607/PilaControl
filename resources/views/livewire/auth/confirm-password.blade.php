<x-layouts::auth title="Confirmar senha" subtitle="Área protegida">
    <x-auth-card>
        <x-auth-session-status :status="session('status')" />

        <p class="text-xs text-muted-foreground">
            Esta é uma área protegida do PilaControl. Confirme sua senha para continuar.
        </p>

        <form method="POST" action="{{ route('password.confirm.store') }}" class="flex flex-col gap-4">
            @csrf

            <flux:input
                name="password"
                label="Senha"
                type="password"
                required
                autofocus
                autocomplete="current-password"
                placeholder="••••••••"
                viewable
            />

            <flux:button
                variant="primary"
                type="submit"
                class="w-full py-2.5! font-semibold!"
                data-test="confirm-password-button"
            >
                Confirmar
            </flux:button>
        </form>
    </x-auth-card>

    <p class="text-center text-xs">
        <flux:link :href="route('profile.edit')" wire:navigate class="text-info! no-underline! hover:underline!">
            ← Voltar às configurações
        </flux:link>
    </p>
</x-layouts::auth>
