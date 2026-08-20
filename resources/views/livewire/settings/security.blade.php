<section class="flex flex-col gap-4">
    @include('partials.settings-heading')

    <x-settings.layout>
        <x-ui.panel heading="Senha">
            <form method="POST" wire:submit="updatePassword" class="flex flex-col gap-4">
                <p class="text-xs text-muted-foreground">
                    Use uma senha longa e que você não repita em outros serviços.
                </p>

                <flux:input
                    wire:model="current_password"
                    label="Senha atual"
                    type="password"
                    required
                    autocomplete="current-password"
                    viewable
                />

                <flux:input
                    wire:model="password"
                    label="Nova senha"
                    type="password"
                    required
                    autocomplete="new-password"
                    placeholder="Mínimo 8 caracteres"
                    passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                    viewable
                />

                <flux:input
                    wire:model="password_confirmation"
                    label="Confirmar nova senha"
                    type="password"
                    required
                    autocomplete="new-password"
                    passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                    viewable
                />

                <div>
                    <flux:button variant="primary" type="submit" class="font-semibold!" data-test="update-password-button">
                        Salvar
                    </flux:button>
                </div>
            </form>
        </x-ui.panel>

        @if ($canManageTwoFactor)
            <x-ui.panel heading="Verificação em duas etapas">
                <div class="flex flex-col gap-4" wire:cloak>
                    @if ($twoFactorEnabled)
                        <div class="flex items-center gap-2">
                            <flux:icon.shield-check variant="mini" class="size-4 shrink-0 text-income" />

                            <span class="text-xs text-income">Ativa nesta conta</span>
                        </div>

                        <p class="text-xs text-muted-foreground">
                            No login, além da senha, pediremos um código de seis dígitos gerado pelo
                            aplicativo autenticador do seu celular.
                        </p>

                        <div>
                            <flux:button variant="danger" size="sm" wire:click="disable">Desativar</flux:button>
                        </div>

                        <livewire:settings.two-factor.recovery-codes :$requiresConfirmation />
                    @else
                        <p class="text-xs text-muted-foreground">
                            Com a verificação em duas etapas, saber sua senha não basta para entrar na
                            conta: também é preciso o código do aplicativo autenticador do seu celular.
                        </p>

                        <div>
                            <flux:button variant="primary" size="sm" wire:click="enable" class="font-semibold!">
                                Ativar
                            </flux:button>
                        </div>
                    @endif
                </div>
            </x-ui.panel>

            <flux:modal
                name="two-factor-setup-modal"
                class="max-w-md! border-border! bg-card! md:min-w-md"
                @close="closeModal"
                wire:model="showModal"
            >
                <div class="flex flex-col gap-6">
                    <div class="flex flex-col items-center gap-3 text-center">
                        <span class="flex size-11 items-center justify-center rounded-full bg-secondary">
                            <flux:icon.qr-code class="size-5 text-muted-foreground" />
                        </span>

                        <div class="flex flex-col gap-1">
                            <flux:heading size="lg">{{ $this->modalConfig['title'] }}</flux:heading>
                            <flux:subheading class="text-xs!">{{ $this->modalConfig['description'] }}</flux:subheading>
                        </div>
                    </div>

                    @if ($showVerificationStep)
                        <div
                            class="flex justify-center"
                            x-data
                            x-init="$nextTick(() => $el.querySelector('input')?.focus())"
                        >
                            <flux:otp name="code" wire:model="code" length="6" label="Código" label:sr-only />
                        </div>

                        <div class="flex gap-2">
                            <flux:button variant="outline" class="flex-1" wire:click="resetVerification">
                                Voltar
                            </flux:button>

                            <flux:button
                                variant="primary"
                                class="flex-1 font-semibold!"
                                wire:click="confirmTwoFactor"
                                x-bind:disabled="$wire.code.length < 6"
                            >
                                Confirmar
                            </flux:button>
                        </div>
                    @else
                        @error('setupData')
                            <x-ui.alert>{{ $message }}</x-ui.alert>
                        @enderror

                        <div class="flex justify-center">
                            <div class="relative aspect-square w-56 overflow-hidden rounded border border-border">
                                @empty($qrCodeSvg)
                                    <div class="absolute inset-0 flex animate-pulse items-center justify-center bg-secondary">
                                        <flux:icon.loading class="text-muted-foreground" />
                                    </div>
                                @else
                                    {{-- O QR precisa de fundo claro para a câmera ler. --}}
                                    <div class="flex h-full items-center justify-center bg-white p-3">
                                        {!! $qrCodeSvg !!}
                                    </div>
                                @endempty
                            </div>
                        </div>

                        <flux:button
                            :disabled="$errors->has('setupData')"
                            variant="primary"
                            class="w-full font-semibold!"
                            wire:click="showVerificationIfNecessary"
                        >
                            {{ $this->modalConfig['buttonText'] }}
                        </flux:button>

                        <div class="flex flex-col gap-3">
                            <div class="relative flex items-center justify-center">
                                <div class="absolute inset-x-0 top-1/2 h-px bg-border"></div>

                                <span class="relative bg-card px-2 text-[10px] tracking-widest text-muted-foreground uppercase">
                                    ou digite o código à mão
                                </span>
                            </div>

                            <div
                                class="flex items-stretch overflow-hidden rounded border border-border"
                                x-data="{
                                    copied: false,
                                    async copy() {
                                        try {
                                            await navigator.clipboard.writeText('{{ $manualSetupKey }}');
                                            this.copied = true;
                                            setTimeout(() => this.copied = false, 1500);
                                        } catch (e) {
                                            console.warn('Não foi possível copiar para a área de transferência');
                                        }
                                    }
                                }"
                            >
                                @empty($manualSetupKey)
                                    <div class="flex w-full items-center justify-center bg-secondary p-3">
                                        <flux:icon.loading variant="mini" class="text-muted-foreground" />
                                    </div>
                                @else
                                    <input
                                        type="text"
                                        readonly
                                        value="{{ $manualSetupKey }}"
                                        class="w-full bg-secondary p-3 font-mono text-xs text-foreground outline-none"
                                    />

                                    <button
                                        type="button"
                                        @click="copy()"
                                        class="cursor-pointer border-s border-border bg-secondary px-3 text-muted-foreground hover:text-foreground"
                                    >
                                        <flux:icon.document-duplicate x-show="!copied" variant="mini" class="size-4" />
                                        <flux:icon.check x-show="copied" variant="mini" class="size-4 text-income" />

                                        <span class="sr-only">Copiar o código</span>
                                    </button>
                                @endempty
                            </div>
                        </div>
                    @endif
                </div>
            </flux:modal>
        @endif
    </x-settings.layout>
</section>
