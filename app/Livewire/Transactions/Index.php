<?php

declare(strict_types=1);

namespace App\Livewire\Transactions;

use App\Enums\TransactionType;
use App\Queries\MonthlySummary;
use App\Queries\Results\PeriodSummary;
use App\Support\Demo\Category;
use App\Support\Demo\Transaction;
use App\Support\DemoData;
use App\Support\Money;
use App\Support\MonthLabel;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * @property-read Collection<string, Category> $categories
 * @property-read Collection<string, Category> $formCategories
 * @property-read array<string, string> $months
 * @property-read Collection<int, Transaction> $all
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

    /**
     * Lançamentos criados e removidos nesta visita.
     *
     * Enquanto a tabela `transactions` não existe, o estado do componente é a única
     * memória possível. Ao criar o model: apagar estas duas propriedades e ligar o
     * formulário a Actions\Transactions\CreateTransaction / DeleteTransaction.
     *
     * @var array<int, array<string, mixed>>
     */
    public array $added = [];

    /**
     * @var array<int, string>
     */
    public array $removed = [];

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
     * @return Collection<string, Category>
     */
    #[Computed]
    public function categories(): Collection
    {
        return DemoData::categories();
    }

    /**
     * Categorias válidas para o tipo escolhido no formulário.
     *
     * @return Collection<string, Category>
     */
    #[Computed]
    public function formCategories(): Collection
    {
        $type = TransactionType::from($this->formType);

        return $this->categories->filter(
            fn (Category $category): bool => $category->type->accepts($type)
        );
    }

    /**
     * Meses com lançamentos, do mais recente ao mais antigo.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function months(): array
    {
        return $this->all
            ->map(fn (Transaction $tx): string => $tx->monthKey())
            ->unique()
            ->sortDesc()
            ->mapWithKeys(fn (string $month): array => [$month => MonthLabel::short($month)])
            ->all();
    }

    /**
     * Base completa, já com as alterações desta visita aplicadas.
     *
     * @return Collection<int, Transaction>
     */
    #[Computed]
    public function all(): Collection
    {
        return DemoData::transactions()
            ->concat($this->addedTransactions())
            ->reject(fn (Transaction $tx): bool => in_array($tx->id, $this->removed, true))
            ->sortByDesc(fn (Transaction $tx): string => $tx->sortKey())
            ->values();
    }

    /**
     * @return Collection<int, Transaction>
     */
    #[Computed]
    public function transactions(): Collection
    {
        return $this->all
            ->when($this->type !== 'all', fn (Collection $rows): Collection => $rows->filter(
                fn (Transaction $tx): bool => $tx->type->value === $this->type
            ))
            ->when($this->search !== '', fn (Collection $rows): Collection => $rows->filter(
                fn (Transaction $tx): bool => str_contains(
                    mb_strtolower($tx->description),
                    mb_strtolower(trim($this->search))
                )
            ))
            ->when($this->categoryId !== '', fn (Collection $rows): Collection => $rows->filter(
                fn (Transaction $tx): bool => $tx->category_id === $this->categoryId
            ))
            ->when($this->month !== '', fn (Collection $rows): Collection => $rows->filter(
                fn (Transaction $tx): bool => $tx->monthKey() === $this->month
            ))
            ->values();
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

    public function save(): void
    {
        $validated = $this->validate([
            'formType' => ['required', 'in:income,expense'],
            'formDescription' => ['required', 'string', 'max:255'],
            'formAmount' => ['required', 'numeric', 'gt:0', 'max:99999999'],
            'formDate' => ['required', 'date'],
            'formCategoryId' => ['required', 'string', 'in:'.$this->formCategories->keys()->implode(',')],
            'formNotes' => ['nullable', 'string', 'max:255'],
        ], attributes: [
            'formType' => 'tipo',
            'formDescription' => 'descrição',
            'formAmount' => 'valor',
            'formDate' => 'data',
            'formCategoryId' => 'categoria',
            'formNotes' => 'observações',
        ]);

        $this->added[] = [
            'id' => 'new-'.count($this->added).'-'.Date::now()->getTimestamp(),
            'date' => $validated['formDate'],
            'description' => $validated['formDescription'],
            'amount_cents' => Money::fromReais($validated['formAmount'])->cents,
            'type' => $validated['formType'],
            'category_id' => $validated['formCategoryId'],
            'notes' => $validated['formNotes'] ?: null,
        ];

        $this->resetForm();
        $this->forgetResults();

        Flux::modal('nova-transacao')->close();
        Flux::toast(variant: 'success', text: 'Transação adicionada.');
    }

    public function delete(string $id): void
    {
        $this->removed[] = $id;
        $this->added = array_values(array_filter(
            $this->added,
            fn (array $row): bool => $row['id'] !== $id,
        ));

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
     * Nome do método importa: o Livewire trata `hydrate{Propriedade}` como hook de
     * ciclo de vida e tenta chamá-lo de fora. `hydrateAdded` colidiria com `$added`.
     *
     * @return Collection<int, Transaction>
     */
    private function addedTransactions(): Collection
    {
        $categories = $this->categories;

        return collect($this->added)->map(fn (array $row): Transaction => new Transaction(
            id: (string) $row['id'],
            date: Date::parse((string) $row['date']),
            description: (string) $row['description'],
            amount_cents: (int) $row['amount_cents'],
            type: TransactionType::from((string) $row['type']),
            category_id: (string) $row['category_id'],
            category: $categories[(string) $row['category_id']],
            notes: $row['notes'] === null ? null : (string) $row['notes'],
        ));
    }

    private function forgetResults(): void
    {
        unset($this->all, $this->transactions, $this->totals, $this->months, $this->hasFilters);
    }
}
