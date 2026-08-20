<x-layouts::auth title="Redefinir senha" subtitle="Escolha uma nova senha">
    <x-auth-card>
        <x-auth-session-status :status="session('status')" />

        <p class="text-xs text-muted-foreground">
            O link é válido por 60 minutos. Escolha a nova senha da sua conta abaixo.
        </p>

        <form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-4">
            @csrf

            <input type="hidden" name="token" value="{{ request()->route('token') }}">

            <flux:input
                name="email"
                label="E-mail"
                :value="request('email')"
                type="email"
                required
                autocomplete="email"
                placeholder="voce@email.com"
            />

            <flux:input
                name="password"
                label="Nova senha"
                type="password"
                required
                autofocus
                autocomplete="new-password"
                placeholder="Mínimo 8 caracteres"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                viewable
            />

            <flux:input
                name="password_confirmation"
                label="Confirmar senha"
                type="password"
                required
                autocomplete="new-password"
                placeholder="Repita a senha"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                viewable
            />

            {{-- Azul, não verde: segundo passo do mesmo fluxo de recuperação do `forgot-password`. --}}
            <flux:button
                type="submit"
                class="w-full border-info! bg-info! py-2.5! font-semibold! text-foreground!"
                data-test="reset-password-button"
            >
                Redefinir senha
            </flux:button>
        </form>
    </x-auth-card>

    <p class="text-center text-xs">
        <flux:link :href="route('login')" wire:navigate class="text-info! no-underline! hover:underline!">
            ← Voltar ao login
        </flux:link>
    </p>
</x-layouts::auth>
