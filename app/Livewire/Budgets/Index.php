<?php

declare(strict_types=1);

namespace App\Livewire\Budgets;

use App\Actions\Budgets\RemoveCategoryBudget;
use App\Actions\Budgets\SetCategoryBudget;
use App\Models\Category;
use App\Queries\BudgetOverview;
use App\Queries\Results\BudgetRow;
use App\Queries\Results\BudgetTotals;
use App\Support\Money;
use App\Support\MonthLabel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
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
     * Id da categoria com o limite em edição.
     */
    public ?int $editing = null;

    public string $editValue = '';

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
        return (new BudgetOverview)->handle(Auth::user(), $this->month);
    }

    #[Computed]
    public function totals(): BudgetTotals
    {
        return (new BudgetOverview)->totals($this->rows);
    }

    public function startEdit(int $categoryId): void
    {
        $this->editing = $categoryId;

        $limit = $this->rows->first(
            fn (BudgetRow $row): bool => $row->category->id === $categoryId
        )?->limit;

        $this->editValue = $limit === null || $limit->isZero()
            ? ''
            : number_format($limit->cents / 100, 2, '.', '');
    }

    public function cancelEdit(): void
    {
        $this->editing = null;
        $this->editValue = '';
    }

    public function saveLimit(
        SetCategoryBudget $setCategoryBudget,
        RemoveCategoryBudget $removeCategoryBudget,
    ): void {
        if ($this->editing === null) {
            return;
        }

        $this->validate([
            'editValue' => ['required', 'numeric', 'min:0', 'max:99999999'],
        ], attributes: ['editValue' => 'limite']);

        $category = $this->category($this->editing);
        $limit = Money::fromReais($this->editValue);

        // Zero digitado é o mesmo que tirar o limite: a tela mostra "Definir
        // limite" nos dois casos, e limite zero não é registro de limite.
        if ($limit->isZero()) {
            $removeCategoryBudget->handle($category, $this->month);
        } else {
            $setCategoryBudget->handle($category, $this->month, $limit);
        }

        $this->cancelEdit();
        $this->forgetResults();
    }

    public function clearLimit(int $categoryId, RemoveCategoryBudget $removeCategoryBudget): void
    {
        $removeCategoryBudget->handle($this->category($categoryId), $this->month);

        $this->cancelEdit();
        $this->forgetResults();
    }

    /**
     * A categoria sai da relação do usuário: id de fora não existe.
     */
    private function category(int $categoryId): Category
    {
        return Auth::user()->categories()->findOrFail($categoryId);
    }

    private function forgetResults(): void
    {
        unset($this->rows, $this->totals);
    }
}
