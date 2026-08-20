<div class="flex flex-col gap-4">
    <div class="flex justify-end">
        <flux:modal.trigger name="nova-meta">
            <flux:button size="sm" variant="primary" icon="plus" class="font-semibold!">Nova Meta</flux:button>
        </flux:modal.trigger>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        @forelse ($this->goals as $goal)
            <flux:card
                wire:key="goal-{{ $goal->id }}"
                class="group flex flex-col gap-4 bg-card! border-border! p-5!"
            >
                {{-- Identificação e prazo --}}
                <div class="flex items-start justify-between gap-3">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="text-2xl" aria-hidden="true">{{ $goal->icon }}</span>

                        <div class="min-w-0">
                            <div class="truncate text-sm font-semibold">{{ $goal->name }}</div>

                            <div class="mt-0.5 text-xs text-muted-foreground">
                                Meta: {{ \App\Support\MonthLabel::date($goal->deadline) }}
                                ·
                                {{ $goal->daysRemaining() > 0
                                    ? trans_choice(':count dia|:count dias', $goal->daysRemaining())
                                    : 'encerrada' }}
                            </div>
                        </div>
                    </div>

                    <flux:button
                        size="xs"
                        variant="subtle"
                        icon="x-mark"
                        wire:click="delete('{{ $goal->id }}')"
                        wire:confirm="Remover a meta {{ $goal->name }}?"
                        class="shrink-0 opacity-0 transition-opacity group-hover:opacity-100 focus-visible:opacity-100 text-muted-foreground!"
                    >
                        <span class="sr-only">Remover {{ $goal->name }}</span>
                    </flux:button>
                </div>

                {{-- Progresso --}}
                <div>
                    <div class="mb-1.5 flex justify-between font-mono text-xs">
                        <span class="text-income">{{ $goal->saved()->format() }}</span>
                        <span class="text-muted-foreground">{{ $goal->target()->format() }}</span>
                    </div>

                    <x-ui.meter
                        :percent="$goal->percent()"
                        :color="$goal->isCompleted() ? 'var(--color-income)' : 'var(--color-info)'"
                        height="h-2"
                    />

                    <div class="mt-1.5 flex justify-between text-xs text-muted-foreground">
                        <span>{{ number_format($goal->percent(), 1, ',', '.') }}% concluído</span>
                        <span>{{ $goal->remaining()->format() }} restante</span>
                    </div>
                </div>

                {{-- Aporte --}}
                @if ($this->depositing === $goal->id)
                    <div class="flex items-center gap-2">
                        <flux:input
                            size="sm"
                            type="number"
                            step="0.01"
                            min="0"
                            placeholder="Valor a depositar"
                            wire:model="depositValue"
                            wire:keydown.enter="saveDeposit"
                            wire:keydown.escape="cancelDeposit"
                            autofocus
                            class="flex-1 font-mono!"
                        />

                        <flux:button size="sm" variant="primary" wire:click="saveDeposit" class="font-semibold!">
                            Depositar
                        </flux:button>

                        <flux:button
                            size="sm"
                            variant="subtle"
                            icon="x-mark"
                            wire:click="cancelDeposit"
                            class="text-muted-foreground!"
                        >
                            <span class="sr-only">Cancelar aporte</span>
                        </flux:button>
                    </div>

                    @error('depositValue')
                        <p class="text-xs text-expense">{{ $message }}</p>
                    @enderror
                @elseif ($goal->isCompleted())
                    <p class="rounded border border-income/27 bg-income/7 py-1.5 text-center text-xs text-income">
                        Meta alcançada
                    </p>
                @else
                    <flux:button
                        size="sm"
                        icon="plus"
                        wire:click="startDeposit('{{ $goal->id }}')"
                        class="border-border! bg-transparent! text-xs! text-info! hover:bg-white/5!"
                    >
                        Depositar
                    </flux:button>
                @endif
            </flux:card>
        @empty
            <p class="col-span-full rounded border border-border bg-card px-4 py-12 text-center text-sm text-muted-foreground">
                Nenhuma meta cadastrada ainda
            </p>
        @endforelse
    </div>

    {{-- Nova meta --}}
    <flux:modal name="nova-meta" class="max-w-md! border-border! bg-card!" wire:close="resetForm">
        <form wire:submit="save" class="flex flex-col gap-4">
            <flux:heading size="lg">Nova Meta</flux:heading>

            <div class="grid grid-cols-[72px_1fr] gap-3">
                <flux:input label="Ícone" wire:model="formIcon" class="text-center! text-lg!" />

                <flux:input label="Nome da Meta" placeholder="Ex: Viagem" wire:model="formName" />
            </div>

            <div class="grid grid-cols-2 gap-3">
                <flux:input
                    label="Valor Alvo (R$)"
                    type="number"
                    step="0.01"
                    min="0"
                    placeholder="0,00"
                    wire:model="formTarget"
                    class="font-mono!"
                />

                <flux:input
                    label="Já Guardado (R$)"
                    type="number"
                    step="0.01"
                    min="0"
                    placeholder="0,00"
                    wire:model="formCurrent"
                    class="font-mono!"
                />
            </div>

            <flux:input label="Prazo" type="date" wire:model="formDeadline" class="font-mono!" />

            <flux:button type="submit" variant="primary" class="py-2.5! font-semibold!">
                Criar Meta
            </flux:button>
        </form>
    </flux:modal>
</div>
