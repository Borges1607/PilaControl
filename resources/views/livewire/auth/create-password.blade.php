<x-layouts::auth title="Defina sua senha" subtitle="Só falta escolher uma senha">
    <x-auth-card>
        <p class="text-xs text-muted-foreground">
            Sua conta foi criada com o Google, que não nos passa uma senha. Escolha uma agora
            para também poder entrar por e-mail — e para conseguir trocá-la depois.
        </p>

        <form method="POST" action="{{ route('password.store') }}" class="flex flex-col gap-4">
            @csrf

            <flux:input
                name="password"
                label="Senha"
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

            <flux:button
                variant="primary"
                type="submit"
                class="w-full py-2.5! font-semibold!"
                data-test="create-password-button"
            >
                Salvar e continuar
            </flux:button>
        </form>
    </x-auth-card>

    {{-- `div`, não `p`: form dentro de parágrafo é HTML inválido e o navegador desmonta a linha. --}}
    <div class="flex items-center justify-center gap-1 text-xs text-muted-foreground">
        <span>Prefere sair?</span>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="text-xs text-info hover:underline">Encerrar a sessão</button>
        </form>
    </div>
</x-layouts::auth>
