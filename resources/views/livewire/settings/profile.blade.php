<section class="flex flex-col gap-4">
    @include('partials.settings-heading')

    <x-settings.layout>
        <x-ui.panel heading="Conta">
            <div class="flex items-center gap-4">
                <flux:avatar
                    size="lg"
                    circle
                    :name="auth()->user()->name"
                    initials:single
                    class="bg-accent! font-bold! text-background!"
                />

                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold">{{ auth()->user()->name }}</p>
                    <p class="truncate font-mono text-xs text-muted-foreground">{{ auth()->user()->email }}</p>

                    <div class="mt-1.5 flex flex-wrap gap-1.5">
                        @if (auth()->user()->google_id)
                            <span class="rounded-sm bg-info/7 px-1.5 py-0.5 text-[10px] text-info">
                                Conectada ao Google
                            </span>
                        @endif

                        <span class="rounded-sm bg-secondary px-1.5 py-0.5 font-mono text-[10px] text-muted-foreground">
                            desde {{ \App\Support\MonthLabel::date(auth()->user()->created_at) }}
                        </span>
                    </div>
                </div>
            </div>
        </x-ui.panel>

        <x-ui.panel heading="Dados pessoais">
            <form wire:submit="updateProfileInformation" class="flex flex-col gap-4">
                <flux:input wire:model="name" label="Nome" type="text" required autofocus autocomplete="name" />

                <div class="flex flex-col gap-2">
                    <flux:input wire:model="email" label="E-mail" type="email" required autocomplete="email" />

                    @if ($this->hasUnverifiedEmail)
                        <x-ui.alert variant="info">
                            Seu e-mail ainda não foi confirmado.

                            <button
                                type="button"
                                wire:click="resendVerificationNotification"
                                class="font-medium underline"
                            >
                                Reenviar a confirmação
                            </button>
                        </x-ui.alert>
                    @endif
                </div>

                <div>
                    <flux:button variant="primary" type="submit" class="font-semibold!">Salvar</flux:button>
                </div>
            </form>
        </x-ui.panel>

        @if ($this->showDeleteUser)
            <livewire:settings.delete-user-form />
        @endif
    </x-settings.layout>
</section>
