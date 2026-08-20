<div
    class="flex flex-col gap-3 rounded border border-border p-4"
    wire:cloak
    x-data="{ showRecoveryCodes: false }"
>
    <div class="flex items-center gap-2">
        <flux:icon.lock-closed variant="mini" class="size-4 shrink-0 text-muted-foreground" />

        <h4 class="text-xs font-semibold tracking-widest text-muted-foreground uppercase">
            Códigos de recuperação
        </h4>
    </div>

    <p class="text-xs text-muted-foreground">
        São a saída caso você perca o celular do autenticador. Guarde num gerenciador de senhas —
        cada código serve uma vez só e some depois de usado.
    </p>

    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <flux:button
            size="sm"
            x-show="!showRecoveryCodes"
            icon="eye"
            @click="showRecoveryCodes = true"
            aria-expanded="false"
            aria-controls="recovery-codes-section"
            class="border-border! bg-transparent! text-muted-foreground! hover:text-foreground!"
        >
            Ver os códigos
        </flux:button>

        <flux:button
            size="sm"
            x-show="showRecoveryCodes"
            icon="eye-slash"
            @click="showRecoveryCodes = false"
            aria-expanded="true"
            aria-controls="recovery-codes-section"
            class="border-border! bg-transparent! text-muted-foreground! hover:text-foreground!"
        >
            Ocultar
        </flux:button>

        @if (filled($recoveryCodes))
            <flux:button
                size="sm"
                x-show="showRecoveryCodes"
                icon="arrow-path"
                wire:click="regenerateRecoveryCodes"
                class="border-border! bg-transparent! text-muted-foreground! hover:text-foreground!"
            >
                Gerar novos
            </flux:button>
        @endif
    </div>

    <div
        x-show="showRecoveryCodes"
        x-transition
        id="recovery-codes-section"
        x-bind:aria-hidden="!showRecoveryCodes"
    >
        <div class="flex flex-col gap-3">
            @error('recoveryCodes')
                <x-ui.alert>{{ $message }}</x-ui.alert>
            @enderror

            @if (filled($recoveryCodes))
                <div
                    class="grid gap-1 rounded bg-secondary p-4 font-mono text-xs"
                    role="list"
                    aria-label="Códigos de recuperação"
                >
                    @foreach ($recoveryCodes as $code)
                        <div role="listitem" class="select-text" wire:loading.class="animate-pulse opacity-50">
                            {{ $code }}
                        </div>
                    @endforeach
                </div>

                <p class="text-[10px] text-muted-foreground">
                    Gerar novos invalida os que estão aí em cima.
                </p>
            @endif
        </div>
    </div>
</div>
