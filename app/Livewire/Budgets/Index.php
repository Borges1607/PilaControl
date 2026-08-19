<?php

declare(strict_types=1);

namespace App\Livewire\Budgets;

use App\Queries\BudgetOverview;
use App\Queries\Results\BudgetRow;
use App\Queries\Results\BudgetTotals;
use App\Support\DemoData;
use App\Support\Money;
use App\Support\MonthLabel;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * @property-read string $month
 * @property-read string $monthLabel
 * @property-read Collection<int, BudgetRow> $rows
 * @property-read BudgetTotals $totals
 */
#[Title('Orçamento')]
class Index extends Component
{
    /**
     * Limites do mês, em centavos, indexados pelo id da categoria.
     *
     * Hoje vivem no estado do componente porque a tabela `budgets` ainda não existe.
     * Quando existir, isto sai e saveLimit() passa a chamar
     * Actions\Budgets\SetCategoryBudget.
     *
     * @var array<string, int>
     */
    public array $limits = [];

    /**
     * Id da categoria com o limite em edição.
     */
    public ?string $editing = null;

    public string $editValue = '';

    public function mount(): void
    {
        $this->limits = DemoData::budgetLimits();
    }

    #[Computed]
    public function month(): string
    {
        return MonthLabel::currentKey();
    }

    #[Computed]
    public function monthLabel(): string
    {
        return MonthLabel::short($this->month);
    }

    /**
     * @return Collection<int, BudgetRow>
     */
    #[Computed]
    public function rows(): Collection
    {
        return (new BudgetOverview)->handle(
            DemoData::transactions(),
            DemoData::categories(),
            $this->limits,
            $this->month,
        );
    }

    #[Computed]
    public function totals(): BudgetTotals
    {
        return (new BudgetOverview)->totals($this->rows);
    }

    public function startEdit(string $categoryId): void
    {
        $this->editing = $categoryId;

        $limit = $this->limits[$categoryId] ?? 0;

        $this->editValue = $limit > 0
            ? number_format($limit / 100, 2, '.', '')
            : '';
    }

    public function cancelEdit(): void
    {
        $this->editing = null;
        $this->editValue = '';
    }

    public function saveLimit(): void
    {
        if ($this->editing === null) {
            return;
        }

        $this->validate([
            'editValue' => ['required', 'numeric', 'min:0', 'max:99999999'],
        ], attributes: ['editValue' => 'limite']);

        $this->limits[$this->editing] = Money::fromReais($this->editValue)->cents;

        $this->cancelEdit();
        $this->forgetResults();
    }

    public function clearLimit(string $categoryId): void
    {
        unset($this->limits[$categoryId]);

        $this->cancelEdit();
        $this->forgetResults();
    }

    private function forgetResults(): void
    {
        unset($this->rows, $this->totals);
    }
}
