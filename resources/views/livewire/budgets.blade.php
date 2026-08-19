<div class="flex flex-col gap-4">
    {{-- Totais do mês --}}
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        <x-ui.stat-card
            label="Orçamento Total"
            :value="$this->totals->budgeted->format()"
            icon="rectangle-group"
        />

        <x-ui.stat-card
            label="Total Gasto"
            :value="$this->totals->spent->format()"
            tone="expense"
            icon="arrow-trending-down"
        />

        <x-ui.stat-card
            label="Disponível"
            :value="$this->totals->available->format()"
            tone="income"
            icon="banknotes"
        />
    </div>

    {{-- Limite por categoria --}}
    <x-ui.panel :heading="'Orçamento por Categoria — '.$this->monthLabel" flush>
        @forelse ($this->rows as $row)
            <div
                wire:key="budget-{{ $row->category->id }}"
                class="grid grid-cols-[28px_1fr_auto] items-center gap-x-4 gap-y-3 border-b border-border px-4 py-4 transition-colors last:border-b-0 hover:bg-white/[0.02] md:grid-cols-[28px_1fr_200px_120px_112px]"
            >
                <span class="text-base" aria-hidden="true">{{ $row->category->icon }}</span>

                <span class="text-sm font-medium">{{ $row->category->name }}</span>

                {{-- Progresso: coluna própria no desktop, linha inteira no mobile --}}
                <div class="col-span-3 flex flex-col gap-1 md:col-span-1 md:col-start-3">
                    @if ($row->percent === null)
                        <span class="text-[10px] text-muted-foreground">Sem limite definido</span>
                    @else
                        <x-ui.meter
                            :percent="$row->percent"
                            :color="$row->over ? 'var(--color-expense)' : $row->category->color"
                            height="h-1.5"
                        />

                        <span class="font-mono text-[10px] text-muted-foreground">
                            {{ number_format($row->percent, 0, ',', '.') }}%{{ $row->over ? ' — excedido' : '' }}
                        </span>
                    @endif
                </div>

                <span class="col-start-3 row-start-1 text-right font-mono text-sm text-expense md:col-start-4 md:text-left">
                    {{ $row->spent->format() }}
                </span>

                <div class="col-span-3 md:col-span-1 md:col-start-5 md:justify-self-end">
                    @if ($this->editing === $row->category->id)
                        <div class="flex items-center gap-1">
                            <flux:input
                                size="sm"
                                type="number"
                                step="0.01"
                                min="0"
                                wire:model="editValue"
                                wire:keydown.enter="saveLimit"
                                wire:keydown.escape="cancelEdit"
                                autofocus
                                class="w-24! font-mono! text-xs!"
                            />

                            <flux:button
                                size="xs"
                                variant="primary"
                                icon="check"
                                wire:click="saveLimit"
                            >
                                <span class="sr-only">Salvar limite</span>
                            </flux:button>

                            <flux:button
                                size="xs"
                                variant="subtle"
                                icon="x-mark"
                                wire:click="cancelEdit"
                                class="text-muted-foreground!"
                            >
                                <span class="sr-only">Cancelar</span>
                            </flux:button>
                        </div>
                    @else
                        <button
                            type="button"
                            wire:click="startEdit('{{ $row->category->id }}')"
                            class="w-full text-left font-mono text-xs hover:underline md:text-right {{ $row->limit->isZero() ? 'text-muted-foreground' : 'text-foreground' }}"
                        >
                            {{ $row->limit->isZero() ? 'Definir limite' : $row->limit->format() }}
                        </button>
                    @endif
                </div>
            </div>
        @empty
            <p class="px-4 py-8 text-center text-sm text-muted-foreground">
                Nenhuma despesa lançada neste mês
            </p>
        @endforelse

        @error('editValue')
            <p class="border-t border-border px-4 py-2 text-xs text-expense">{{ $message }}</p>
        @enderror
    </x-ui.panel>
</div>
