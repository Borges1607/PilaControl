<section>
    {{-- Painel de zona de risco: a borda vermelha é o aviso, não a decoração. --}}
    <flux:card class="overflow-hidden border-expense/27! bg-card! p-0!">
        <div class="border-b border-expense/27 px-4 py-3">
            <h3 class="text-xs font-semibold tracking-widest text-expense uppercase">Excluir conta</h3>
        </div>

        <div class="flex flex-col gap-4 p-4">
            <p class="text-xs text-muted-foreground">
                A exclusão é definitiva: some a conta e tudo que está ligado a ela — lançamentos,
                orçamentos e metas. Não há como desfazer.
            </p>

            <div>
                <flux:modal.trigger name="confirmar-exclusao">
                    <flux:button variant="danger" size="sm" icon="trash">Excluir minha conta</flux:button>
                </flux:modal.trigger>
            </div>
        </div>
    </flux:card>

    <flux:modal
        name="confirmar-exclusao"
        :show="$errors->isNotEmpty()"
        focusable
        class="max-w-md! border-border! bg-card!"
    >
        <form method="POST" wire:submit="deleteUser" class="flex flex-col gap-4">
            <div class="flex flex-col gap-1">
                <flux:heading size="lg">Excluir a conta?</flux:heading>

                <flux:subheading class="text-xs!">
                    Confirme com sua senha. Tudo que está na conta será apagado e não dá para voltar atrás.
                </flux:subheading>
            </div>

            <flux:input wire:model="password" label="Senha" type="password" viewable />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled" size="sm">Cancelar</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" size="sm" type="submit">Excluir definitivamente</flux:button>
            </div>
        </form>
    </flux:modal>
</section>
