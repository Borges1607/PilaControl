<?php

declare(strict_types=1);

namespace App\Livewire\Transactions;

use App\Actions\Transactions\CreateTransaction;
use App\Actions\Transactions\DeleteTransaction;
use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\Transaction;
use App\Queries\MonthlySummary;
use App\Queries\Results\PeriodSummary;
use App\Support\Money;
use App\Support\MonthLabel;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * @property-read Collection<int, Category> $categories
 * @property-read Collection<int, Category> $formCategories
 * @property-read array<string, string> $months
 * @property-read Collection<int, Transaction> $transactions
 * @property-read PeriodSummary $totals
 * @property-read bool $hasFilters
 */
#[Title('Transações')]
class Index extends Component
{
    /**
     * "all", "income" ou "expense".
     */
    #[Url]
    public string $type = 'all';

    #[Url]
    public string $search = '';

    #[Url(as: 'categoria')]
    public string $categoryId = '';

    #[Url(as: 'mes')]
    public string $month = '';

    // Formulário do modal de nova transação.
    public string $formType = 'expense';

    public string $formDescription = '';

    public string $formAmount = '';

    public string $formDate = '';

    public string $formCategoryId = '';

    public string $formNotes = '';

    public function mount(): void
    {
        $this->formDate = Date::now()->format('Y-m-d');
    }

    /**
     * O registro do usuário — alimenta o filtro de categoria.
     *
     * @return Collection<int, Category>
     */
    #[Computed]
    public function categories(): Collection
    {
        return Auth::user()->categories()->inRegistryOrder()->get()->keyBy('id');
    }

    /**
     * Categorias válidas para o tipo escolhido no formulário.
     *
     * @return Collection<int, Category>
     */
    #[Computed]
    public function formCategories(): Collection
    {
        $type = TransactionType::from($this->formType);

        return $this->categories->filter(
            fn (Category $category): bool => $category->accepts($type)
        );
    }

    /**
     * Meses com lançamentos, do mais recente ao mais antigo.
     *
     * Só a coluna `date` sai do banco; o agrupamento em "Y-m" é feito em PHP
     * para a consulta não depender do `strftime` do SQLite.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function months(): array
    {
        return Auth::user()->transactions()
            ->latestFirst()
            ->get(['date'])
            ->map(fn (Transaction $tx): string => $tx->monthKey())
            ->unique()
            ->mapWithKeys(fn (string $month): array => [$month => MonthLabel::short($month)])
            ->all();
    }

    /**
     * O recorte atual, com os filtros aplicados no banco.
     *
     * @return Collection<int, Transaction>
     */
    #[Computed]
    public function transactions(): Collection
    {
        $search = trim($this->search);

        return Auth::user()->transactions()
            // A tabela mostra ícone e pílula de cada linha: sem isto é 1 + N.
            ->with('category')
            ->when($this->type !== 'all', fn (Builder $query): Builder => $query->where('type', $this->type))
            ->when($search !== '', fn (Builder $query): Builder => $query->whereLike('description', "%{$search}%"))
            ->when($this->categoryId !== '', fn (Builder $query): Builder => $query->where('category_id', (int) $this->categoryId))
            // Mês só entra se vier na forma "Y-m" — o valor chega da URL.
            ->when($this->isMonthKey($this->month), fn (Builder $query): Builder => $query->inMonth($this->month))
            ->latestFirst()
            ->get();
    }

    #[Computed]
    public function totals(): PeriodSummary
    {
        return (new MonthlySummary)->handle($this->transactions);
    }

    #[Computed]
    public function hasFilters(): bool
    {
        return $this->type !== 'all'
            || $this->search !== ''
            || $this->categoryId !== ''
            || $this->month !== '';
    }

    public function setType(string $type): void
    {
        if (! in_array($type, ['all', 'income', 'expense'], true)) {
            return;
        }

        $this->type = $type;

        $this->forgetResults();
    }

    public function clearFilters(): void
    {
        $this->type = 'all';
        $this->search = '';
        $this->categoryId = '';
        $this->month = '';

        $this->forgetResults();
    }

    public function updated(): void
    {
        $this->forgetResults();
    }

    public function updatedFormType(): void
    {
        $this->formCategoryId = '';

        unset($this->formCategories);
    }

    public function save(CreateTransaction $createTransaction): void
    {
        $validated = $this->validate([
            'formType' => ['required', 'in:income,expense'],
            'formDescription' => ['required', 'string', 'max:255'],
            'formAmount' => ['required', 'numeric', 'gt:0', 'max:99999999'],
            'formDate' => ['required', 'date'],
            // A lista é só das compatíveis com o tipo: categoria de receita não
            // recebe despesa, e o seletor já mostra apenas essas.
            'formCategoryId' => ['required', Rule::in($this->formCategoryIds())],
            'formNotes' => ['nullable', 'string', 'max:255'],
        ], attributes: [
            'formType' => 'tipo',
            'formDescription' => 'descrição',
            'formAmount' => 'valor',
            'formDate' => 'data',
            'formCategoryId' => 'categoria',
            'formNotes' => 'observações',
        ]);

        $createTransaction->handle(
            category: $this->categories[(int) $validated['formCategoryId']],
            type: TransactionType::from($validated['formType']),
            description: $validated['formDescription'],
            amount: Money::fromReais($validated['formAmount']),
            date: Date::parse($validated['formDate']),
            notes: $validated['formNotes'] ?: null,
        );

        $this->resetForm();
        $this->forgetResults();

        Flux::modal('nova-transacao')->close();
        Flux::toast(variant: 'success', text: 'Transação adicionada.');
    }

    public function delete(int $id, DeleteTransaction $deleteTransaction): void
    {
        // A busca sai da relação do usuário; a policy é a segunda tranca.
        $transaction = Auth::user()->transactions()->findOrFail($id);

        $this->authorize('delete', $transaction);

        $deleteTransaction->handle($transaction);

        $this->forgetResults();

        Flux::toast(variant: 'success', text: 'Transação removida.');
    }

    public function resetForm(): void
    {
        $this->formType = 'expense';
        $this->formDescription = '';
        $this->formAmount = '';
        $this->formDate = Date::now()->format('Y-m-d');
        $this->formCategoryId = '';
        $this->formNotes = '';

        $this->resetValidation();

        unset($this->formCategories);
    }

    /**
     * Ids aceitos no seletor, como string: é assim que o valor chega do `<select>`
     * e é assim que a regra `in` compara.
     *
     * @return array<int, string>
     */
    private function formCategoryIds(): array
    {
        return $this->formCategories
            ->map(fn (Category $category): string => (string) $category->id)
            ->values()
            ->all();
    }

    private function isMonthKey(string $month): bool
    {
        return preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month) === 1;
    }

    private function forgetResults(): void
    {
        unset($this->transactions, $this->totals, $this->months, $this->hasFilters);
    }
}
