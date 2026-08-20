<x-layouts::auth title="Entrar" subtitle="Acesse sua conta">
    <x-auth-card>
        <x-auth-session-status :status="session('status')" />

        @error('google')
            <x-ui.alert>{{ $message }}</x-ui.alert>
        @enderror

        <x-google-button />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-4">
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

            <flux:input
                name="password"
                label="Senha"
                type="password"
                required
                autocomplete="current-password"
                placeholder="••••••••"
                viewable
            />

            <div class="flex items-center justify-between gap-3">
                <flux:checkbox name="remember" label="Lembrar de mim" :checked="old('remember')" />

                @if (Route::has('password.request'))
                    <flux:link
                        :href="route('password.request')"
                        wire:navigate
                        class="text-xs! text-info! no-underline! hover:underline!"
                    >
                        Esqueceu a senha?
                    </flux:link>
                @endif
            </div>

            <flux:button
                variant="primary"
                type="submit"
                class="w-full py-2.5! font-semibold!"
                data-test="login-button"
            >
                Entrar
            </flux:button>
        </form>
    </x-auth-card>

    <p class="text-center text-xs text-muted-foreground">
        Não tem conta?
        <flux:link :href="route('register')" wire:navigate class="text-info! no-underline! hover:underline!">
            Criar conta
        </flux:link>
    </p>
</x-layouts::auth>
