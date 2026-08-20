<x-layouts::auth title="Recuperar senha" subtitle="Recuperar senha">
    <x-auth-card>
        <x-auth-session-status :status="session('status')" />

        <p class="text-xs text-muted-foreground">
            Informe seu e-mail cadastrado e enviaremos um link para você redefinir a senha.
        </p>

        <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-4">
            @csrf

            <flux:input
                name="email"
                label="E-mail"
                :value="old('email')"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="voce@email.com"
            />

            {{-- Azul, não verde: no protótipo as telas de recuperação usam o `--accent`. --}}
            <flux:button
                type="submit"
                class="w-full border-info! bg-info! py-2.5! font-semibold! text-foreground!"
                data-test="email-password-reset-link-button"
            >
                Enviar link
            </flux:button>
        </form>
    </x-auth-card>

    <p class="text-center text-xs">
        <flux:link :href="route('login')" wire:navigate class="text-info! no-underline! hover:underline!">
            ← Voltar ao login
        </flux:link>
    </p>
</x-layouts::auth>
