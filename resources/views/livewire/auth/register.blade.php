<x-layouts::auth title="Criar conta" subtitle="Crie sua conta gratuita">
    <x-auth-card>
        <x-auth-session-status :status="session('status')" />

        @error('google')
            <x-ui.alert>{{ $message }}</x-ui.alert>
        @enderror

        <x-google-button label="Cadastrar com o Google" separator="ou cadastre com e-mail" />

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-4">
            @csrf

            <flux:input
                name="name"
                label="Nome"
                :value="old('name')"
                type="text"
                required
                autofocus
                autocomplete="name"
                placeholder="Seu nome"
            />

            <flux:input
                name="email"
                label="E-mail"
                :value="old('email')"
                type="email"
                required
                autocomplete="email"
                placeholder="voce@email.com"
            />

            <flux:input
                name="password"
                label="Senha"
                type="password"
                required
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

            <flux:button
                variant="primary"
                type="submit"
                class="w-full py-2.5! font-semibold!"
                data-test="register-user-button"
            >
                Criar conta
            </flux:button>
        </form>
    </x-auth-card>

    <p class="text-center text-xs text-muted-foreground">
        Já tem conta?
        <flux:link :href="route('login')" wire:navigate class="text-info! no-underline! hover:underline!">
            Entrar
        </flux:link>
    </p>
</x-layouts::auth>
