<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\CategoryType;
use App\Enums\TransactionType;
use App\Support\Demo\Category;
use App\Support\Demo\Goal;
use App\Support\Demo\Transaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;

/**
 * TEMPORÁRIO — conteúdo de vitrine, copiado de `src/data.ts` do protótipo.
 *
 * Existe apenas para as telas terem dados enquanto os models Category, Transaction
 * e Budget não existem. Os objetos de `App\Support\Demo` expõem as mesmas colunas
 * que os models terão, então as views não mudam na troca.
 *
 * Ao criar os models: apagar este arquivo e o namespace `App\Support\Demo`, e trocar
 * o type hint das classes em `app/Queries` por `App\Models\*`. Nada mais deve mudar.
 */
final class DemoData
{
    /**
     * @var Collection<string, Category>|null
     */
    private static ?Collection $categories = null;

    /**
     * @return Collection<string, Category> indexada pelo id da categoria
     */
    public static function categories(): Collection
    {
        return self::$categories ??= collect([
            new Category('salary', 'Salário', '💼', '#00e676', CategoryType::Income),
            new Category('freelance', 'Freelance', '💻', '#29b6f6', CategoryType::Income),
            new Category('investments', 'Investimentos', '📈', '#ab47bc', CategoryType::Income),
            new Category('other-in', 'Outros', '💰', '#ffca28', CategoryType::Income),
            new Category('housing', 'Moradia', '🏠', '#f85149', CategoryType::Expense),
            new Category('food', 'Alimentação', '🍽️', '#ff7043', CategoryType::Expense),
            new Category('transport', 'Transporte', '🚗', '#ffa726', CategoryType::Expense),
            new Category('health', 'Saúde', '🏥', '#26a69a', CategoryType::Expense),
            new Category('education', 'Educação', '📚', '#42a5f5', CategoryType::Expense),
            new Category('leisure', 'Lazer', '🎯', '#ec407a', CategoryType::Expense),
            new Category('shopping', 'Compras', '🛍️', '#7e57c2', CategoryType::Expense),
            new Category('bills', 'Contas', '📄', '#8d6e63', CategoryType::Expense),
            new Category('other-ex', 'Outros', '📦', '#78909c', CategoryType::Expense),
        ])->keyBy(fn (Category $category): string => $category->id);
    }

    /**
     * Três meses de lançamentos, do mais recente ao mais antigo.
     *
     * @return Collection<int, Transaction>
     */
    public static function transactions(): Collection
    {
        $income = TransactionType::Income;
        $expense = TransactionType::Expense;

        return collect([
            self::make('t1', 0, 5, 'Salário do mês', 8500, $income, 'salary'),
            self::make('t2', 0, 5, 'Aluguel', 1800, $expense, 'housing'),
            self::make('t3', 0, 6, 'Supermercado Extra', 387.50, $expense, 'food'),
            self::make('t4', 0, 7, 'Uber', 42.90, $expense, 'transport'),
            self::make('t5', 0, 8, 'Projeto Site Cliente A', 1200, $income, 'freelance'),
            self::make('t6', 0, 9, 'Farmácia', 98.70, $expense, 'health'),
            self::make('t7', 0, 10, 'Netflix + Spotify', 67, $expense, 'bills'),
            self::make('t8', 0, 10, 'Restaurante', 156.40, $expense, 'food'),
            self::make('t9', 0, 12, 'Curso Udemy', 49.90, $expense, 'education'),
            self::make('t10', 0, 13, 'Dividendos ITUB4', 340, $income, 'investments'),
            self::make('t11', 0, 14, 'Gasolina', 210, $expense, 'transport'),
            self::make('t12', 0, 15, 'Conta de Luz', 189.30, $expense, 'bills'),
            self::make('t13', 0, 15, 'Academia', 120, $expense, 'health'),
            self::make('t14', 0, 16, 'Projeto App Mobile', 2400, $income, 'freelance'),
            self::make('t15', 0, 17, 'Ingresso Show', 280, $expense, 'leisure'),
            self::make('t16', 0, 17, 'Amazon', 425.60, $expense, 'shopping'),
            self::make('t17', 0, 18, 'Água e Gás', 87, $expense, 'bills'),
            self::make('t18', 1, 1, 'Salário do mês', 8500, $income, 'salary'),
            self::make('t19', 1, 1, 'Aluguel', 1800, $expense, 'housing'),
            self::make('t20', 1, 3, 'Supermercado', 412, $expense, 'food'),
            self::make('t21', 1, 5, 'Freelance App', 900, $income, 'freelance'),
            self::make('t22', 1, 8, 'Restaurantes', 210, $expense, 'food'),
            self::make('t23', 1, 10, 'Streaming', 67, $expense, 'bills'),
            self::make('t24', 1, 12, 'Gasolina', 195, $expense, 'transport'),
            self::make('t25', 1, 15, 'Dividendos PETR4', 280, $income, 'investments'),
            self::make('t26', 1, 18, 'Shopping', 390, $expense, 'shopping'),
            self::make('t27', 2, 1, 'Salário do mês', 8500, $income, 'salary'),
            self::make('t28', 2, 1, 'Aluguel', 1800, $expense, 'housing'),
            self::make('t29', 2, 4, 'Supermercado', 356, $expense, 'food'),
            self::make('t30', 2, 6, 'Freelance', 1500, $income, 'freelance'),
            self::make('t31', 2, 10, 'Viagem RJ', 890, $expense, 'leisure'),
            self::make('t32', 2, 14, 'Farmácia', 145, $expense, 'health'),
            self::make('t33', 2, 16, 'Contas', 320, $expense, 'bills'),
        ])
            ->sortByDesc(fn (Transaction $tx): string => $tx->sortKey())
            ->values();
    }

    /**
     * Limites do mês corrente.
     *
     * @return array<string, int> id da categoria => limite em centavos
     */
    public static function budgetLimits(): array
    {
        return [
            'housing' => Money::fromReais(1800)->cents,
            'food' => Money::fromReais(800)->cents,
            'transport' => Money::fromReais(400)->cents,
            'health' => Money::fromReais(300)->cents,
            'leisure' => Money::fromReais(400)->cents,
            'shopping' => Money::fromReais(500)->cents,
            'bills' => Money::fromReais(350)->cents,
        ];
    }

    /**
     * Metas do protótipo. Os prazos acompanham o ano corrente, como no `INITIAL_GOALS`.
     *
     * @return Collection<int, Goal>
     */
    public static function goals(): Collection
    {
        $year = (int) Date::now()->format('Y');

        return collect([
            self::goal('g1', 'Fundo de Emergência', '🛡️', 30000, 18500, ($year + 1).'-03-01'),
            self::goal('g2', 'Viagem Europa', '✈️', 15000, 4200, ($year + 1).'-07-01'),
            self::goal('g3', 'Notebook Novo', '💻', 6000, 3800, $year.'-11-01'),
            self::goal('g4', 'Reserva Investimentos', '📈', 50000, 22000, ($year + 2).'-01-01'),
        ]);
    }

    /**
     * @param  float  $target  valor alvo em reais
     * @param  float  $current  valor já guardado, em reais
     * @param  string  $deadline  data "Y-m-d"
     */
    private static function goal(
        string $id,
        string $name,
        string $icon,
        float $target,
        float $current,
        string $deadline,
    ): Goal {
        return new Goal(
            id: $id,
            name: $name,
            icon: $icon,
            target_cents: Money::fromReais($target)->cents,
            current_cents: Money::fromReais($current)->cents,
            deadline: Date::parse($deadline)->startOfDay(),
        );
    }

    /**
     * @param  int  $monthsAgo  0 = mês corrente
     * @param  int  $day  dia do mês
     * @param  float  $amount  valor em reais
     */
    private static function make(
        string $id,
        int $monthsAgo,
        int $day,
        string $description,
        float $amount,
        TransactionType $type,
        string $categoryId,
    ): Transaction {
        return new Transaction(
            id: $id,
            date: Date::now()->startOfMonth()->subMonths($monthsAgo)->addDays($day - 1),
            description: $description,
            amount_cents: Money::fromReais($amount)->cents,
            type: $type,
            category_id: $categoryId,
            category: self::categories()[$categoryId],
        );
    }
}
