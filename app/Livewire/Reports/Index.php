<?php

declare(strict_types=1);

namespace App\Livewire\Reports;

use App\Queries\BalanceTimeline;
use App\Queries\Results\CategorySpending;
use App\Queries\Results\MonthPoint;
use App\Queries\SpendingByCategory;
use App\Support\Demo\Transaction;
use App\Support\DemoData;
use App\Support\Money;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * @property-read Collection<int, Transaction> $transactions
 * @property-read string $since
 * @property-read Collection<int, MonthPoint> $timeline
 * @property-read Collection<int, CategorySpending> $ranking
 * @property-read Money $totalExpense
 * @property-read array<string, array{labels: array<int, string>, series: array<int, array<string, mixed>>}> $charts
 */
#[Title('Relatórios')]
class Index extends Component
{
    /**
     * Períodos oferecidos, em meses.
     */
    public const PERIODS = [3, 6, 12];

    #[Url(as: 'periodo')]
    public int $period = 6;

    /**
     * Fonte de dados provisória — ver App\Support\DemoData.
     *
     * @return Collection<int, Transaction>
     */
    #[Computed]
    public function transactions(): Collection
    {
        return DemoData::transactions();
    }

    /**
     * Primeiro mês do recorte. Período de 6 meses inclui o corrente e os cinco anteriores.
     */
    #[Computed]
    public function since(): string
    {
        return Date::now()->startOfMonth()->subMonths($this->period - 1)->format('Y-m');
    }

    /**
     * @return Collection<int, MonthPoint>
     */
    #[Computed]
    public function timeline(): Collection
    {
        return (new BalanceTimeline)->handle($this->transactions, $this->since);
    }

    /**
     * @return Collection<int, CategorySpending>
     */
    #[Computed]
    public function ranking(): Collection
    {
        return (new SpendingByCategory)->since($this->transactions, $this->since);
    }

    #[Computed]
    public function totalExpense(): Money
    {
        return Money::sum($this->ranking->map(fn (CategorySpending $row): Money => $row->total));
    }

    /**
     * Dados dos três gráficos, indexados pelo `name` do <x-ui.chart>.
     *
     * Fonte única: a Blade lê daqui na primeira renderização e o setPeriod() manda
     * exatamente o mesmo formato pelo evento `chart:data`.
     *
     * @return array<string, array{labels: array<int, string>, series: array<int, array<string, mixed>>}>
     */
    #[Computed]
    public function charts(): array
    {
        $months = $this->timeline->pluck('label')->all();

        return [
            'receitas-despesas' => [
                'labels' => $months,
                'series' => [
                    [
                        'label' => 'Receita',
                        'color' => 'income',
                        'data' => $this->timeline->map(fn (MonthPoint $row): float => $row->income->toReais())->all(),
                    ],
                    [
                        'label' => 'Despesa',
                        'color' => 'expense',
                        'data' => $this->timeline->map(fn (MonthPoint $row): float => $row->expense->toReais())->all(),
                    ],
                ],
            ],
            'evolucao-saldo' => [
                'labels' => $months,
                'series' => [
                    [
                        'label' => 'Saldo',
                        'color' => 'info',
                        'fill' => true,
                        'data' => $this->timeline->map(fn (MonthPoint $row): float => $row->balance()->toReais())->all(),
                    ],
                ],
            ],
            'despesas-categoria' => [
                'labels' => $this->ranking->map(fn (CategorySpending $row): string => $row->category->name)->all(),
                'series' => [
                    [
                        'label' => 'Despesa',
                        'colors' => $this->ranking->map(fn (CategorySpending $row): string => $row->category->color)->all(),
                        'data' => $this->ranking->map(fn (CategorySpending $row): float => $row->total->toReais())->all(),
                    ],
                ],
            ],
        ];
    }

    public function setPeriod(int $period): void
    {
        if (! in_array($period, self::PERIODS, true)) {
            return;
        }

        $this->period = $period;

        unset($this->since, $this->timeline, $this->ranking, $this->totalExpense, $this->charts);

        // Os gráficos vivem sob `wire:ignore`, então o Livewire não os redesenha:
        // cada um se atualiza ao ouvir o evento que traz o seu próprio nome.
        foreach ($this->charts as $name => $payload) {
            $this->dispatch('chart:data', name: $name, labels: $payload['labels'], series: $payload['series']);
        }
    }
}
