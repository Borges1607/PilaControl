@php
    $filters = [
        'all' => 'Todas',
        'income' => 'Receitas',
        'expense' => 'Despesas',
    ];
@endphp

<div class="flex flex-col gap-4">
    {{-- Filtros e busca --}}
    <div class="flex flex-wrap items-center justify-between gap-2">
        <div class="flex flex-wrap items-center gap-2">
            @foreach ($filters as $value => $label)
                <flux:button
                    size="sm"
                    wire:click="setType('{{ $value }}')"
                    class="{{ $this->type === $value
                        ? 'border-info! bg-info! text-white!'
                        : 'border-border! bg-secondary! text-muted-foreground! hover:text-foreground!' }}"
                >
                    {{ $label }}
                </flux:button>
            @endforeach

            <flux:select wire:model.live="categoryId" size="sm" class="w-auto! min-w-44">
                <flux:select.option value="">Todas categorias</flux:select.option>

                @foreach ($this->categories as $category)
                    <flux:select.option :value="$category->id">
                        {{ $category->icon }} {{ $category->name }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="month" size="sm" class="w-auto! min-w-36">
                <flux:select.option value="">Todos os meses</flux:select.option>

                @foreach ($this->months as $key => $label)
                    <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>

            @if ($this->hasFilters)
                <flux:button size="sm" variant="ghost" wire:click="clearFilters" class="text-muted-foreground!">
                    Limpar
                </flux:button>
            @endif
        </div>

        <div class="flex items-center gap-2">
            <flux:input
                size="sm"
                icon="magnifying-glass"
                placeholder="Buscar…"
                wire:model.live.debounce.300ms="search"
                class="w-48!"
            />

            <flux:modal.trigger name="nova-transacao">
                <flux:button size="sm" variant="primary" icon="plus">Nova</flux:button>
            </flux:modal.trigger>
        </div>
    </div>

    {{-- Totais do recorte atual --}}
    <div class="flex flex-wrap gap-3">
        <div class="flex items-center gap-1.5 rounded border border-income/20 bg-income/8 px-3 py-1.5 font-mono text-xs text-income">
            <flux:icon.arrow-up-right variant="micro" class="size-3" />
            {{ $this->totals->income->format() }}
        </div>

        <div class="flex items-center gap-1.5 rounded border border-expense/20 bg-expense/8 px-3 py-1.5 font-mono text-xs text-expense">
            <flux:icon.arrow-down-right variant="micro" class="size-3" />
            {{ $this->totals->expense->format() }}
        </div>

        <div class="rounded border border-border bg-secondary px-3 py-1.5 font-mono text-xs text-muted-foreground">
            {{ trans_choice(':count transação|:count transações', $this->totals->count) }}
        </div>
    </div>

    {{-- Lançamentos --}}
    <x-ui.panel flush>
        <x-ui.tx-table :transactions="$this->transactions" delete-action="delete" scroll />
    </x-ui.panel>

    {{-- Nova transação --}}
    <flux:modal
        name="nova-transacao"
        class="max-w-md! border-border! bg-card!"
        wire:close="resetForm"
    >
        <form wire:submit="save" class="flex flex-col gap-4">
            <flux:heading size="lg">Nova Transação</flux:heading>

            {{-- Tipo: despesa ou receita --}}
            <div class="flex overflow-hidden rounded border border-border">
                @foreach ([\App\Enums\TransactionType::Expense, \App\Enums\TransactionType::Income] as $case)
                    <button
                        type="button"
                        wire:click="$set('formType', '{{ $case->value }}')"
                        @class([
                            'flex-1 py-2 text-sm font-medium transition-colors',
                            'bg-expense/13 text-expense' => $this->formType === $case->value && ! $case->isIncome(),
                            'bg-income/13 text-income' => $this->formType === $case->value && $case->isIncome(),
                            'text-muted-foreground hover:text-foreground' => $this->formType !== $case->value,
                        ])
                    >
                        {{ $case->label() }}
                    </button>
                @endforeach
            </div>

            <flux:input
                label="Descrição"
                placeholder="Ex: Supermercado"
                wire:model="formDescription"
            />

            <div class="grid grid-cols-2 gap-3">
                <flux:input
                    label="Valor (R$)"
                    type="number"
                    step="0.01"
                    min="0"
                    placeholder="0,00"
                    wire:model="formAmount"
                    class="font-mono!"
                />

                <flux:input
                    label="Data"
                    type="date"
                    wire:model="formDate"
                    class="font-mono!"
                />
            </div>

            <flux:select label="Categoria" wire:model="formCategoryId">
                <flux:select.option value="">Selecionar…</flux:select.option>

                @foreach ($this->formCategories as $category)
                    <flux:select.option :value="$category->id">
                        {{ $category->icon }} {{ $category->name }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            <flux:input
                label="Observações (opcional)"
                placeholder="Notas adicionais"
                wire:model="formNotes"
            />

            <flux:button
                type="submit"
                class="{{ $this->formType === 'income'
                    ? 'bg-income! text-background!'
                    : 'bg-expense! text-background!' }} border-transparent! font-semibold!"
            >
                Adicionar
            </flux:button>
        </form>
    </flux:modal>
</div>
