<?php

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Três meses de lançamentos para uma conta, no formato do protótipo: um salário
 * e três despesas por mês, sempre nas mesmas quatro categorias do conjunto padrão.
 *
 * Serve às telas de leitura (Dashboard e Relatórios), que precisam de série
 * mensal e de ranking — não de um lançamento avulso.
 */
function seedLedger(User $user): void
{
    $category = fn (string $name): Category => $user->categories()
        ->where('name', $name)
        ->sole();

    $salario = $category('Salário');
    $moradia = $category('Moradia');
    $alimentacao = $category('Alimentação');
    $transporte = $category('Transporte');

    foreach ([0, 1, 2] as $monthsAgo) {
        $start = now()->startOfMonth()->subMonths($monthsAgo);

        $on = fn (int $day): string => $start->copy()->addDays($day)->format('Y-m-d');

        Transaction::factory()->for($salario)->income()->worth(8500)->on($on(0))->create();
        Transaction::factory()->for($moradia)->worth(1800)->on($on(1))->create();
        Transaction::factory()->for($alimentacao)->worth(400)->on($on(2))->create();
        Transaction::factory()->for($transporte)->worth(200)->on($on(3))->create();
    }
}
