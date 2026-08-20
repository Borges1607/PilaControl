<x-layouts::auth title="Verificação em duas etapas" subtitle="Confirme que é você">
    {{--
        O `x-data` envolve o cartão **e** o rodapé: o link que alterna entre código do
        autenticador e código de recuperação vive embaixo do cartão e precisa do mesmo escopo.
    --}}
    <div
        class="flex flex-col gap-6"
        x-cloak
        x-data="{
            showRecoveryInput: @js($errors->has('recovery_code')),
            code: '',
            recovery_code: '',
            focusOtp() {
                this.$nextTick(() => this.$refs.otp?.querySelector('input')?.focus());
            },
            init() {
                if (! this.showRecoveryInput) {
                    this.focusOtp();
                }
            },
            toggleInput() {
                this.showRecoveryInput = !this.showRecoveryInput;

                this.code = '';
                this.recovery_code = '';

                $nextTick(() => {
                    this.showRecoveryInput
                        ? this.$refs.recovery_code?.focus()
                        : this.focusOtp();
                });
            },
        }"
    >
        <x-auth-card>
            <p class="text-xs text-muted-foreground" x-show="!showRecoveryInput">
                Informe o código de seis dígitos gerado pelo seu aplicativo autenticador.
            </p>

            <p class="text-xs text-muted-foreground" x-show="showRecoveryInput">
                Informe um dos códigos de recuperação que você guardou ao ativar a verificação
                em duas etapas.
            </p>

            <form method="POST" action="{{ route('two-factor.login.store') }}" class="flex flex-col gap-4">
                @csrf

                <div x-show="!showRecoveryInput">
                    <div class="flex items-center justify-center" x-ref="otp">
                        <flux:otp
                            x-model="code"
                            length="6"
                            name="code"
                            label="Código de verificação"
                            label:sr-only
                            class="mx-auto"
                        />
                    </div>
                </div>

                <div x-show="showRecoveryInput">
                    <flux:input
                        type="text"
                        name="recovery_code"
                        label="Código de recuperação"
                        x-ref="recovery_code"
                        x-bind:required="showRecoveryInput"
                        autocomplete="one-time-code"
                        x-model="recovery_code"
                    />

                    @error('recovery_code')
                        <x-ui.alert class="mt-2">{{ $message }}</x-ui.alert>
                    @enderror
                </div>

                <flux:button
                    variant="primary"
                    type="submit"
                    class="w-full py-2.5! font-semibold!"
                    data-test="two-factor-login-button"
                >
                    Continuar
                </flux:button>
            </form>
        </x-auth-card>

        <p class="text-center text-xs text-muted-foreground">
            <button
                type="button"
                class="text-xs text-info hover:underline"
                x-show="!showRecoveryInput"
                @click="toggleInput()"
            >
                Usar um código de recuperação
            </button>

            <button
                type="button"
                class="text-xs text-info hover:underline"
                x-show="showRecoveryInput"
                @click="toggleInput()"
            >
                Usar o código do autenticador
            </button>
        </p>
    </div>
</x-layouts::auth>
