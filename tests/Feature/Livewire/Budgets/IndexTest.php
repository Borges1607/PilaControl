<?php

declare(strict_types=1);

use App\Livewire\Budgets\Index;
use App\Models\User;
use App\Support\Money;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
});

it('renderiza os totais e as linhas por categoria', function (): void {
    Livewire::test(Index::class)
        ->assertOk()
        ->assertSee('Orçamento Total')
        ->assertSee('Total Gasto')
        ->assertSee('Disponível')
        ->assertSee('Alimentação');
});

it('soma orçado, gasto e disponível', function (): void {
    $component = Livewire::test(Index::class);
    $rows = $component->instance()->rows;
    $totals = $component->instance()->totals;

    expect($totals->budgeted->cents)->toBe((int) $rows->sum(fn ($row): int => $row->limit->cents))
        ->and($totals->spent->cents)->toBe((int) $rows->sum(fn ($row): int => $row->spent->cents));
});

it('grava um novo limite para a categoria', function (): void {
    Livewire::test(Index::class)
        ->call('startEdit', 'food')
        ->assertSet('editing', 'food')
        ->set('editValue', '1250.50')
        ->call('saveLimit')
        ->assertSet('editing', null)
        ->assertSet('limits.food', Money::fromReais(1250.50)->cents);
});

it('recusa limite inválido', function (): void {
    Livewire::test(Index::class)
        ->call('startEdit', 'food')
        ->set('editValue', 'abc')
        ->call('saveLimit')
        ->assertHasErrors(['editValue' => 'numeric'])
        ->assertSet('editing', 'food');
});

it('remove o limite da categoria', function (): void {
    $component = Livewire::test(Index::class)->call('clearLimit', 'food');

    expect($component->instance()->limits)->not->toHaveKey('food');
});
