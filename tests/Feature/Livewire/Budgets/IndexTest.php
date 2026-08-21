<?php

declare(strict_types=1);

use App\Enums\TransactionType;
use App\Livewire\Budgets\Index;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Queries\Results\BudgetRow;
use App\Support\Money;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->user = User::factory()->create();

    $this->actingAs($this->user);

    $this->alimentacao = $this->user->categories()->where('name', 'Alimentação')->sole();
    $this->moradia = $this->user->categories()->where('name', 'Moradia')->sole();

    // Alimentação: limite de 800 e 300 gastos no mês.
    Budget::factory()->for($this->alimentacao)->worth(800)->create(['user_id' => $this->user->id]);

    Transaction::factory()->for($this->alimentacao)->worth(300)->create([
        'user_id' => $this->user->id,
        'type' => TransactionType::Expense,
        'date' => now()->startOfMonth(),
    ]);

    // Moradia: 1800 gastos, sem limite.
    Transaction::factory()->for($this->moradia)->worth(1800)->create([
        'user_id' => $this->user->id,
        'type' => TransactionType::Expense,
        'date' => now()->startOfMonth(),
    ]);
});

it('renderiza os totais e as linhas por categoria', function (): void {
    Livewire::test(Index::class)
        ->assertOk()
        ->assertSee('Orçamento Total')
        ->assertSee('Total Gasto')
        ->assertSee('Disponível')
        ->assertSee('Alimentação', escape: false)
        ->assertSee('Sem limite definido');
});

it('mostra só categoria com gasto no mês ou com limite', function (): void {
    $component = Livewire::test(Index::class);

    // Ordem de cadastro das categorias: Moradia vem antes de Alimentação.
    expect($component->instance()->rows->pluck('category.name')->all())
        ->toBe(['Moradia', 'Alimentação']);
});

it('soma orçado, gasto e disponível do mês', function (): void {
    $totals = Livewire::test(Index::class)->instance()->totals;

    expect($totals->budgeted->cents)->toBe(80_000)
        ->and($totals->spent->cents)->toBe(210_000)
        // Disponível não fica negativo: soma só o que sobra por linha.
        ->and($totals->available->cents)->toBe(50_000);
});

it('ignora gasto de outro mês', function (): void {
    Transaction::factory()->for($this->alimentacao)->worth(999)->create([
        'user_id' => $this->user->id,
        'type' => TransactionType::Expense,
        'date' => now()->subMonth()->startOfMonth(),
    ]);

    $row = Livewire::test(Index::class)->instance()->rows
        ->first(fn (BudgetRow $row): bool => $row->category->is($this->alimentacao));

    expect($row->spent->cents)->toBe(30_000);
});

it('marca a linha que passou do limite', function (): void {
    Transaction::factory()->for($this->alimentacao)->worth(600)->create([
        'user_id' => $this->user->id,
        'type' => TransactionType::Expense,
        'date' => now()->startOfMonth(),
    ]);

    $row = Livewire::test(Index::class)->instance()->rows
        ->first(fn (BudgetRow $row): bool => $row->category->is($this->alimentacao));

    expect($row->over)->toBeTrue()
        // A barra não passa do fim, mesmo excedida.
        ->and($row->percent)->toBe(100.0);
});

it('não mistura o orçamento de dois usuários', function (): void {
    $outro = User::factory()->create();
    $categoriaAlheia = $outro->categories()->where('name', 'Lazer')->sole();

    Budget::factory()->for($categoriaAlheia)->worth(5000)->create(['user_id' => $outro->id]);

    expect(Livewire::test(Index::class)->instance()->totals->budgeted->cents)->toBe(80_000);
});

it('abre a edição com o limite atual', function (): void {
    Livewire::test(Index::class)
        ->call('startEdit', $this->alimentacao->id)
        ->assertSet('editing', $this->alimentacao->id)
        ->assertSet('editValue', '800.00');
});

it('abre a edição vazia quando não há limite', function (): void {
    Livewire::test(Index::class)
        ->call('startEdit', $this->moradia->id)
        ->assertSet('editValue', '');
});

it('grava um novo limite para a categoria', function (): void {
    Livewire::test(Index::class)
        ->call('startEdit', $this->moradia->id)
        ->set('editValue', '1250.50')
        ->call('saveLimit')
        ->assertSet('editing', null)
        ->assertSet('editValue', '');

    expect($this->moradia->budgets()->forMonth(now()->format('Y-m'))->value('limit_cents'))
        ->toBe(Money::fromReais(1250.50)->cents);
});

it('atualiza o limite existente em vez de duplicar', function (): void {
    Livewire::test(Index::class)
        ->call('startEdit', $this->alimentacao->id)
        ->set('editValue', '900')
        ->call('saveLimit');

    expect($this->alimentacao->budgets()->count())->toBe(1)
        ->and($this->alimentacao->budgets()->value('limit_cents'))->toBe(90_000);
});

it('recusa limite inválido', function (): void {
    Livewire::test(Index::class)
        ->call('startEdit', $this->alimentacao->id)
        ->set('editValue', 'abc')
        ->call('saveLimit')
        ->assertHasErrors(['editValue' => 'numeric'])
        ->assertSet('editing', $this->alimentacao->id);

    expect($this->alimentacao->budgets()->value('limit_cents'))->toBe(80_000);
});

it('trata zero digitado como tirar o limite', function (): void {
    Livewire::test(Index::class)
        ->call('startEdit', $this->alimentacao->id)
        ->set('editValue', '0')
        ->call('saveLimit')
        ->assertHasNoErrors();

    expect($this->alimentacao->budgets()->count())->toBe(0);
});

it('remove o limite da categoria', function (): void {
    Livewire::test(Index::class)->call('clearLimit', $this->alimentacao->id);

    expect($this->alimentacao->budgets()->count())->toBe(0);
});

it('não aceita categoria de outro usuário nem pela ação direta', function (): void {
    $alheia = Category::factory()->create();

    expect(fn (): mixed => Livewire::test(Index::class)->call('clearLimit', $alheia->id))
        ->toThrow(ModelNotFoundException::class);
});
