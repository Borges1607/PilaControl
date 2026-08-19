<div class="flex flex-col gap-6">
    {{-- Indicadores do mês --}}
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-ui.stat-card
            label="Saldo Acumulado"
            :value="$this->accumulated->format()"
            :tone="$this->accumulated->isNegative() ? 'expense' : 'income'"
            icon="wallet"
        />

        <x-ui.stat-card
            label="Receitas / Mês"
            :value="$this->summary->income->format()"
            :sub="trans_choice(':count transação|:count transações', $this->summary->incomeCount)"
            tone="income"
            icon="arrow-trending-up"
        />

        <x-ui.stat-card
            label="Despesas / Mês"
            :value="$this->summary->expense->format()"
            :sub="trans_choice(':count transação|:count transações', $this->summary->expenseCount)"
            tone="expense"
            icon="arrow-trending-down"
        />

        <x-ui.stat-card
            label="Saldo / Mês"
            :value="$this->summary->balance->format(sign: true)"
            :sub="$this->summary->balance->isNegative() ? 'déficit' : 'superávit'"
            :tone="$this->summary->balance->isNegative() ? 'expense' : 'income'"
            icon="scale"
        />
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        {{-- Receitas vs despesas por mês --}}
        <x-ui.panel heading="Receitas vs Despesas" class="lg:col-span-2">
            <x-ui.chart
                type="bar"
                :height="200"
                :labels="$this->timeline->pluck('label')->all()"
                :series="[
                    [
                        'label' => 'Receita',
                        'color' => 'income',
                        'data' => $this->timeline->map(fn ($row) => $row->income->toReais())->all(),
                    ],
                    [
                        'label' => 'Despesa',
                        'color' => 'expense',
                        'data' => $this->timeline->map(fn ($row) => $row->expense->toReais())->all(),
                    ],
                ]"
            />
        </x-ui.panel>

        {{-- Maiores despesas do mês --}}
        <x-ui.panel heading="Top Despesas — Mês">
            @if ($this->topExpenses->isEmpty())
                <p class="text-sm text-muted-foreground">Sem dados</p>
            @else
                <div class="flex flex-col gap-3">
                    @foreach ($this->topExpenses as $row)
                        <div class="flex flex-col gap-1">
                            <div class="flex items-baseline justify-between gap-2 text-xs">
                                <span class="truncate">
                                    <span aria-hidden="true">{{ $row->category->icon }}</span>
                                    {{ $row->category->name }}
                                </span>
                                <span class="shrink-0 font-mono text-expense">{{ $row->total->format() }}</span>
                            </div>

                            <x-ui.meter :percent="$row->share" :color="$row->category->color" />
                        </div>
                    @endforeach
                </div>
            @endif
        </x-ui.panel>
    </div>

    {{-- Últimos lançamentos --}}
    <x-ui.panel heading="Transações Recentes" flush>
        <x-slot:actions>
            <flux:link
                :href="route('transactions.index')"
                wire:navigate
                class="text-xs! font-medium text-info! no-underline!"
            >
                Ver todas
            </flux:link>
        </x-slot:actions>

        <x-ui.tx-table :transactions="$this->recent" empty="Nenhuma transação lançada ainda" />
    </x-ui.panel>
</div>
