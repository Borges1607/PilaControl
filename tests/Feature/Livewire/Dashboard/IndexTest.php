<?php

declare(strict_types=1);

use App\Livewire\Dashboard\Index;
use App\Models\Transaction;
use App\Models\User;
use App\Queries\Results\CategorySpending;
use App\Queries\Results\MonthPoint;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->user = User::factory()->create();

    $this->actingAs($this->user);

    // Um salário e três despesas por mês, nos três últimos meses — ver tests/Pest.php.
    seedLedger($this->user);
});

it('mostra os indicadores do mês', function (): void {
    Livewire::test(Index::class)
        ->assertOk()
        ->assertSee('Saldo Acumulado')
        ->assertSee('Receitas / Mês')
        ->assertSee('Despesas / Mês')
        ->assertSee('Saldo / Mês');
});

it('lista os painéis do protótipo', function (): void {
    Livewire::test(Index::class)
        ->assertSee('Receitas vs Despesas')
        ->assertSee('Top Despesas — Mês', escape: false)
        ->assertSee('Transações Recentes');
});

it('traz no máximo oito lançamentos recentes, do mais novo ao mais antigo', function (): void {
    $recent = Livewire::test(Index::class)->instance()->recent;

    expect($recent)->toHaveCount(8)
        ->and($recent->first()->date->greaterThanOrEqualTo($recent->last()->date))->toBeTrue();
});

it('resume o mês corrente com receitas e despesas', function (): void {
    $summary = Livewire::test(Index::class)->instance()->summary;

    expect($summary->income->cents)->toBe(850_000)
        ->and($summary->expense->cents)->toBe(240_000)
        ->and($summary->balance->cents)->toBe(610_000)
        ->and($summary->incomeCount)->toBe(1)
        ->and($summary->expenseCount)->toBe(3)
        ->and($summary->count)->toBe(4);
});

it('acumula o saldo de todos os meses', function (): void {
    // Três meses de 6.100 de sobra.
    expect(Livewire::test(Index::class)->instance()->accumulated->cents)->toBe(1_830_000);
});

it('monta a série mensal do mais antigo ao mais recente', function (): void {
    $timeline = Livewire::test(Index::class)->instance()->timeline;

    expect($timeline)->toHaveCount(3)
        ->and($timeline->first()->month)->toBe(now()->subMonths(2)->format('Y-m'))
        ->and($timeline->last()->month)->toBe(now()->format('Y-m'))
        ->and($timeline->last()->balance()->cents)->toBe(610_000)
        ->and($timeline->every(fn (MonthPoint $point): bool => $point->income->cents === 850_000))
        ->toBeTrue();
});

it('ranqueia as maiores despesas do mês', function (): void {
    $top = Livewire::test(Index::class)->instance()->topExpenses;

    expect($top->pluck('category.name')->all())->toBe(['Moradia', 'Alimentação', 'Transporte'])
        ->and($top->first()->total->cents)->toBe(180_000)
        // A fatia é sobre a despesa do mês: 1.800 de 2.400.
        ->and($top->first()->share)->toBe(75.0);
});

it('não mistura os números de dois usuários', function (): void {
    seedLedger(User::factory()->create());

    $summary = Livewire::test(Index::class)->instance()->summary;

    expect($summary->income->cents)->toBe(850_000)
        ->and($summary->count)->toBe(4);
});

it('aguenta conta sem lançamento nenhum', function (): void {
    Transaction::query()->delete();

    $component = Livewire::test(Index::class)->assertOk()->assertSee('Sem dados');

    expect($component->instance()->accumulated->isZero())->toBeTrue()
        ->and($component->instance()->timeline)->toBeEmpty()
        ->and($component->instance()->topExpenses)->toBeEmpty()
        ->and($component->instance()->recent)->toBeEmpty();
});

it('limita as maiores despesas a cinco categorias', function (): void {
    foreach (['Saúde', 'Educação', 'Lazer', 'Compras'] as $index => $name) {
        Transaction::factory()
            ->for($this->user->categories()->where('name', $name)->sole())
            ->worth(50 + $index)
            ->on(now()->startOfMonth()->format('Y-m-d'))
            ->create();
    }

    $top = Livewire::test(Index::class)->instance()->topExpenses;

    expect($top)->toHaveCount(5)
        ->and($top->map(fn (CategorySpending $row): int => $row->total->cents)->all())
        ->toBe([180_000, 40_000, 20_000, 5_300, 5_200]);
});

it('exige autenticação', function (): void {
    auth()->logout();

    $this->get(route('dashboard'))->assertRedirect(route('login'));
});
