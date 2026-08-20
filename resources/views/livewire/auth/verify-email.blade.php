<x-layouts::auth title="Verificar e-mail" subtitle="Confirme seu endereço">
    <x-auth-card>
        <p class="text-xs text-muted-foreground">
            Enviamos um link de verificação para o seu e-mail. Clique nele para liberar o acesso
            à sua conta.
        </p>

        @if (session('status') == 'verification-link-sent')
            <x-ui.alert variant="success">Um novo link de verificação foi enviado para o seu e-mail.</x-ui.alert>
        @endif

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <flux:button variant="primary" type="submit" class="w-full py-2.5! font-semibold!">
                Reenviar link
            </flux:button>
        </form>
    </x-auth-card>

    {{-- `div`, não `p`: form dentro de parágrafo é HTML inválido e o navegador desmonta a linha. --}}
    <div class="flex items-center justify-center gap-1 text-xs text-muted-foreground">
        <span>Prefere sair?</span>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="text-xs text-info hover:underline" data-test="logout-button">
                Encerrar a sessão
            </button>
        </form>
    </div>
</x-layouts::auth>
