<div class="flex flex-col gap-6">
    {{-- Período --}}
    <div class="flex gap-2">
        @foreach (\App\Livewire\Reports\Index::PERIODS as $months)
            <flux:button
                size="sm"
                wire:click="setPeriod({{ $months }})"
                class="{{ $this->period === $months
                    ? 'border-info! bg-info! text-white!'
                    : 'border-border! bg-secondary! text-muted-foreground! hover:text-foreground!' }}"
            >
                {{ $months }} meses
            </flux:button>
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        {{--
            Os gráficos não são redesenhados pelo Livewire (estão sob `wire:ignore`): o
            `setPeriod()` manda os novos dados pelo evento `chart:data`, e cada canvas
            aceita só o que traz o seu `name`.
        --}}
        <x-ui.panel heading="Receitas vs Despesas">
            <x-ui.chart
                name="receitas-despesas"
                type="bar"
                :height="240"
                :labels="$this->charts['receitas-despesas']['labels']"
                :series="$this->charts['receitas-despesas']['series']"
            />
        </x-ui.panel>

        <x-ui.panel heading="Evolução do Saldo">
            <x-ui.chart
                name="evolucao-saldo"
                type="line"
                :height="240"
                :labels="$this->charts['evolucao-saldo']['labels']"
                :series="$this->charts['evolucao-saldo']['series']"
            />
        </x-ui.panel>

        <x-ui.panel heading="Despesas por Categoria">
            @if ($this->ranking->isEmpty())
                <p class="py-16 text-center text-sm text-muted-foreground">Sem despesas no período</p>
            @else
                <x-ui.chart
                    name="despesas-categoria"
                    type="doughnut"
                    :height="240"
                    :labels="$this->charts['despesas-categoria']['labels']"
                    :series="$this->charts['despesas-categoria']['series']"
                />
            @endif
        </x-ui.panel>

        <x-ui.panel heading="Ranking de Gastos">
            @if ($this->ranking->isEmpty())
                <p class="py-16 text-center text-sm text-muted-foreground">Sem despesas no período</p>
            @else
                <div class="flex max-h-[220px] flex-col gap-2 overflow-y-auto">
                    @foreach ($this->ranking as $row)
                        <div class="flex items-center gap-3" wire:key="ranking-{{ $row->category->id }}">
                            <span class="w-4 text-right font-mono text-xs text-muted-foreground">
                                {{ $loop->iteration }}
                            </span>

                            <div class="min-w-0 flex-1">
                                <div class="mb-1 flex justify-between gap-2 text-xs">
                                    <span class="truncate">
                                        <span aria-hidden="true">{{ $row->category->icon }}</span>
                                        {{ $row->category->name }}
                                    </span>

                                    <span class="shrink-0 font-mono text-expense">{{ $row->total->format() }}</span>
                                </div>

                                <x-ui.meter :percent="$row->share" :color="$row->category->color" />
                            </div>

                            <span class="w-8 text-right font-mono text-[10px] text-muted-foreground">
                                {{ number_format($row->share, 0, ',', '.') }}%
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-ui.panel>
    </div>
</div>
