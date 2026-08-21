<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\Budgets\SetCategoryBudget;
use App\Actions\Goals\CreateGoal;
use App\Actions\Transactions\CreateTransaction;
use App\Enums\CategoryType;
use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\User;
use App\Support\Money;
use App\Support\MonthLabel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Date;
use RuntimeException;

/**
 * Os dados de vitrine do protótipo, agora no banco.
 *
 * Substitui o antigo `Support\DemoData`, que servia as telas direto do PHP: três
 * meses de lançamentos, os limites do mês corrente e as quatro metas. Serve para
 * desenvolvimento — nenhuma tela depende disto para funcionar.
 *
 *     php artisan db:seed --class=DemoSeeder      # o primeiro usuário do banco
 *
 * Sem argumento pega o primeiro usuário; o `DatabaseSeeder` passa a conta de teste
 * que acabou de criar. Passa pelas mesmas Actions da interface, de propósito: se
 * uma regra mudar e o conteúdo do protótipo deixar de ser válido, o seeder é o
 * primeiro a reclamar.
 */
class DemoSeeder extends Seeder
{
    public function run(?User $user = null): void
    {
        $user ??= User::query()->firstOrFail();

        // Idempotente: rodar duas vezes não duplica nada, e conta que já tem
        // história não é sobrescrita.
        if ($user->transactions()->exists()) {
            return;
        }

        $this->transactions($user);
        $this->budgets($user);
        $this->goals($user);
    }

    private function transactions(User $user): void
    {
        $income = TransactionType::Income;
        $expense = TransactionType::Expense;

        // [meses atrás, dia, descrição, valor em reais, tipo, categoria]
        $rows = [
            [0, 5, 'Salário do mês', 8500, $income, 'Salário'],
            [0, 5, 'Aluguel', 1800, $expense, 'Moradia'],
            [0, 6, 'Supermercado Extra', 387.50, $expense, 'Alimentação'],
            [0, 7, 'Uber', 42.90, $expense, 'Transporte'],
            [0, 8, 'Projeto Site Cliente A', 1200, $income, 'Freelance'],
            [0, 9, 'Farmácia', 98.70, $expense, 'Saúde'],
            [0, 10, 'Netflix + Spotify', 67, $expense, 'Contas'],
            [0, 10, 'Restaurante', 156.40, $expense, 'Alimentação'],
            [0, 12, 'Curso Udemy', 49.90, $expense, 'Educação'],
            [0, 13, 'Dividendos ITUB4', 340, $income, 'Investimentos'],
            [0, 14, 'Gasolina', 210, $expense, 'Transporte'],
            [0, 15, 'Conta de Luz', 189.30, $expense, 'Contas'],
            [0, 15, 'Academia', 120, $expense, 'Saúde'],
            [0, 16, 'Projeto App Mobile', 2400, $income, 'Freelance'],
            [0, 17, 'Ingresso Show', 280, $expense, 'Lazer'],
            [0, 17, 'Amazon', 425.60, $expense, 'Compras'],
            [0, 18, 'Água e Gás', 87, $expense, 'Contas'],
            [1, 1, 'Salário do mês', 8500, $income, 'Salário'],
            [1, 1, 'Aluguel', 1800, $expense, 'Moradia'],
            [1, 3, 'Supermercado', 412, $expense, 'Alimentação'],
            [1, 5, 'Freelance App', 900, $income, 'Freelance'],
            [1, 8, 'Restaurantes', 210, $expense, 'Alimentação'],
            [1, 10, 'Streaming', 67, $expense, 'Contas'],
            [1, 12, 'Gasolina', 195, $expense, 'Transporte'],
            [1, 15, 'Dividendos PETR4', 280, $income, 'Investimentos'],
            [1, 18, 'Shopping', 390, $expense, 'Compras'],
            [2, 1, 'Salário do mês', 8500, $income, 'Salário'],
            [2, 1, 'Aluguel', 1800, $expense, 'Moradia'],
            [2, 4, 'Supermercado', 356, $expense, 'Alimentação'],
            [2, 6, 'Freelance', 1500, $income, 'Freelance'],
            [2, 10, 'Viagem RJ', 890, $expense, 'Lazer'],
            [2, 14, 'Farmácia', 145, $expense, 'Saúde'],
            [2, 16, 'Contas', 320, $expense, 'Contas'],
        ];

        $action = app(CreateTransaction::class);

        foreach ($rows as [$monthsAgo, $day, $description, $amount, $type, $categoryName]) {
            $action->handle(
                category: $this->category($user, $categoryName, $type),
                type: $type,
                description: $description,
                amount: Money::fromReais($amount),
                date: Date::now()->startOfMonth()->subMonths($monthsAgo)->addDays($day - 1),
            );
        }
    }

    private function budgets(User $user): void
    {
        $limits = [
            'Moradia' => 1800,
            'Alimentação' => 800,
            'Transporte' => 400,
            'Saúde' => 300,
            'Lazer' => 400,
            'Compras' => 500,
            'Contas' => 350,
        ];

        $action = app(SetCategoryBudget::class);
        $month = MonthLabel::currentKey();

        foreach ($limits as $categoryName => $reais) {
            $action->handle(
                $this->category($user, $categoryName, TransactionType::Expense),
                $month,
                Money::fromReais($reais),
            );
        }
    }

    private function goals(User $user): void
    {
        $year = (int) Date::now()->format('Y');

        // [nome, ícone, alvo, já guardado, prazo]
        $rows = [
            ['Fundo de Emergência', '🛡️', 30_000, 18_500, ($year + 1).'-03-01'],
            ['Viagem Europa', '✈️', 15_000, 4_200, ($year + 1).'-07-01'],
            ['Notebook Novo', '💻', 6_000, 3_800, ($year + 1).'-11-01'],
            ['Reserva Investimentos', '📈', 50_000, 22_000, ($year + 2).'-01-01'],
        ];

        $action = app(CreateGoal::class);

        foreach ($rows as [$name, $icon, $target, $current, $deadline]) {
            $action->handle(
                user: $user,
                name: $name,
                icon: $icon,
                target: Money::fromReais($target),
                current: Money::fromReais($current),
                deadline: Date::parse($deadline),
            );
        }
    }

    /**
     * A categoria do conjunto padrão que aceita o tipo. O nome sozinho não basta:
     * há uma "Outros" de receita e uma de despesa.
     */
    private function category(User $user, string $name, TransactionType $type): Category
    {
        $category = $user->categories()
            ->where('name', $name)
            ->whereIn('type', [CategoryType::from($type->value), CategoryType::Both])
            ->first();

        if (! $category instanceof Category) {
            throw new RuntimeException(
                "{$user->email} não tem a categoria {$name} de {$type->label()}."
            );
        }

        return $category;
    }
}
