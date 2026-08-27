<?php

declare(strict_types=1);

namespace App\Livewire\Transactions;

use App\Actions\Transactions\CreateInstallmentTransactions;
use App\Actions\Transactions\CreateTransaction;
use App\Actions\Transactions\DeleteTransaction;
use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\Transaction;
use App\Queries\MonthlySummary;
use App\Queries\Results\PeriodSummary;
use App\Support\Money;
use App\Support\MonthLabel;
use Carbon\CarbonInterface;
use Closure;
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
 * @property-read list<array{index: int, label: string, month: string}> $installmentRows
 * @property-read Money $installmentsTotal
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

    public bool $formInstallment = false;

    public string $formInstallmentCount = '12';

    public array $formInstallments = [];

    private const MIN_INSTALLMENTS = 2;

    private const MAX_INSTALLMENTS = 60;

    private const DESCRIPTION_LIMIT = 255;

    private const MAX_CENTS = 9_999_999_900;

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

    /**
     * Totais do recorte visível. Soma as linhas já carregadas para a tabela — o
     * recorte vem de filtros quaisquer, não de um mês, e elas já estão em mãos.
     */
    #[Computed]
    public function totals(): PeriodSummary
    {
        return (new MonthlySummary)->fromRows($this->transactions);
    }

    #[Computed]
    public function hasFilters(): bool
    {
        return $this->type !== 'all'
            || $this->search !== ''
            || $this->categoryId !== ''
            || $this->month !== '';
    }

    #[Computed]
    public function installmentRows(): array
    {
        if (! $this->formInstallment) {
            return [];
        }

        $first = $this->firstInstallmentDate();
        $count = count($this->formInstallments);

        return array_map(
            fn (int $index): array => [
                'index' => $index,
                'label' => ($index + 1).'/'.$count,
                'month' => MonthLabel::short($first->copy()->addMonthsNoOverflow($index)),
            ],
            array_keys($this->formInstallments),
        );
    }

    #[Computed]
    public function installmentsTotal(): Money
    {
        return Money::sum(array_map(
            fn (string $amount): Money => Money::fromInput($amount),
            $this->formInstallments,
        ));
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

    public function updatedFormInstallment(): void
    {
        if ($this->formInstallment) {
            $this->spreadAmountOverInstallments();
        } else {
            $this->formInstallments = [];
        }

        $this->resetValidation();
    }

    public function updatedFormInstallmentCount(): void
    {
        $this->formInstallmentCount = (string) $this->installmentCount();

        $this->spreadAmountOverInstallments();
    }

    public function updatedFormAmount(): void
    {
        if ($this->formInstallment) {
            $this->spreadAmountOverInstallments();
        }
    }

    public function updatedFormInstallments(): void
    {
        unset($this->installmentsTotal);

        $this->formAmount = $this->installmentsTotal->toInput();
    }

    public function updatedFormDate(): void
    {
        unset($this->installmentRows);
    }

    public function save(
        CreateTransaction $createTransaction,
        CreateInstallmentTransactions $createInstallmentTransactions,
    ): void {
        $validated = $this->validate($this->formRules(), attributes: [
            'formType' => 'tipo',
            'formDescription' => 'descrição',
            'formAmount' => 'valor',
            'formDate' => 'data',
            'formCategoryId' => 'categoria',
            'formNotes' => 'observações',
            'formInstallmentCount' => 'número de parcelas',
            'formInstallments' => 'parcelas',
            'formInstallments.*' => 'valor da parcela',
        ]);

        $category = $this->categories[(int) $validated['formCategoryId']];
        $type = TransactionType::from($validated['formType']);
        $date = Date::parse($validated['formDate']);
        $notes = $validated['formNotes'] ?: null;

        if ($this->formInstallment) {
            $installments = $createInstallmentTransactions->handle(
                category: $category,
                type: $type,
                description: $validated['formDescription'],
                amounts: array_map(
                    fn (string $amount): Money => Money::fromInput($amount),
                    array_values($validated['formInstallments']),
                ),
                firstDate: $date,
                notes: $notes,
            );

            $message = trans_choice(
                ':count parcela adicionada.|:count parcelas adicionadas.',
                $installments->count(),
            );
        } else {
            $createTransaction->handle(
                category: $category,
                type: $type,
                description: $validated['formDescription'],
                amount: Money::fromInput($validated['formAmount']),
                date: $date,
                notes: $notes,
            );

            $message = 'Transação adicionada.';
        }

        $this->resetForm();
        $this->forgetResults();

        Flux::modal('nova-transacao')->close();
        Flux::toast(variant: 'success', text: $message);
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
        $this->formInstallment = false;
        $this->formInstallmentCount = '12';
        $this->formInstallments = [];

        $this->resetValidation();

        unset($this->formCategories, $this->installmentRows, $this->installmentsTotal);
    }

    /**
     * As regras mudam com o parcelamento ligado: aí quem vale é a lista de
     * parcelas, e o campo de valor vira só o espelho da soma delas.
     *
     * @return array<string, array<int, mixed>>
     */
    private function formRules(): array
    {
        $rules = [
            'formType' => ['required', 'in:income,expense'],
            'formDescription' => ['required', 'string', 'max:'.$this->descriptionLimit()],
            'formAmount' => ['required', self::moneyRule()],
            'formDate' => ['required', 'date'],
            // A lista é só das compatíveis com o tipo: categoria de receita não
            // recebe despesa, e o seletor já mostra apenas essas.
            'formCategoryId' => ['required', Rule::in($this->formCategoryIds())],
            'formNotes' => ['nullable', 'string', 'max:255'],
        ];

        if (! $this->formInstallment) {
            return $rules;
        }

        // Nada a cobrar do total: ele é derivado das parcelas, e exigi-lo aqui
        // só duplicaria o erro que elas já dão.
        $rules['formAmount'] = ['nullable'];

        $rules['formInstallmentCount'] = [
            'required', 'integer',
            'min:'.self::MIN_INSTALLMENTS,
            'max:'.self::MAX_INSTALLMENTS,
        ];

        $rules['formInstallments'] = ['array', 'size:'.$this->installmentCount()];
        $rules['formInstallments.*'] = ['required', self::moneyRule()];

        return $rules;
    }

    /**
     * Reparte o valor do campo de total em partes iguais, com o resto nas
     * primeiras — daí o usuário ajusta parcela a parcela se precisar.
     */
    private function spreadAmountOverInstallments(): void
    {
        $this->formInstallments = array_map(
            fn (Money $part): string => $part->toInput(),
            Money::fromInput($this->formAmount)->split($this->installmentCount()),
        );

        unset($this->installmentRows, $this->installmentsTotal);
    }

    /**
     * O número de parcelas dentro dos limites — o campo é digitado à mão e
     * chega como veio.
     */
    private function installmentCount(): int
    {
        return max(
            self::MIN_INSTALLMENTS,
            min(self::MAX_INSTALLMENTS, (int) $this->formInstallmentCount),
        );
    }

    /**
     * Quanto sobra para a descrição depois do sufixo "(12/12)".
     */
    private function descriptionLimit(): int
    {
        if (! $this->formInstallment) {
            return self::DESCRIPTION_LIMIT;
        }

        $count = $this->installmentCount();

        return self::DESCRIPTION_LIMIT - mb_strlen(sprintf(' (%d/%d)', $count, $count));
    }

    /**
     * A data da primeira parcela. Vem do `<input type="date">`, mas pode chegar
     * vazia ou torta — aí o cronograma se apoia em hoje até o campo ficar bom.
     */
    private function firstInstallmentDate(): CarbonInterface
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->formDate) === 1
            ? Date::parse($this->formDate)
            : Date::now();
    }

    /**
     * Campo de dinheiro chega com máscara ("1.234,56"), então `numeric` não
     * serve — quem vale é o valor que se lê dela.
     */
    private static function moneyRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $cents = Money::fromInput(is_string($value) ? $value : null)->cents;

            if ($cents <= 0) {
                $fail('O campo :attribute deve ser maior que zero.')->translate();
            } elseif ($cents > self::MAX_CENTS) {
                $fail('O campo :attribute passa do valor máximo de R$ 99.999.999,00.')->translate();
            }
        };
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
