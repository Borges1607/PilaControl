<?php

declare(strict_types=1);

use App\Livewire\Dashboard\Index;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
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

it('traz no máximo oito lançamentos recentes', function (): void {
    $component = Livewire::test(Index::class);

    expect($component->instance()->recent)->toHaveCount(8);
});

it('resume o mês corrente com receitas e despesas', function (): void {
    $summary = Livewire::test(Index::class)->instance()->summary;

    expect($summary->income->cents)->toBeGreaterThan(0)
        ->and($summary->expense->cents)->toBeGreaterThan(0)
        ->and($summary->balance->cents)->toBe($summary->income->cents - $summary->expense->cents);
});

it('exige autenticação', function (): void {
    auth()->logout();

    $this->get(route('dashboard'))->assertRedirect(route('login'));
});
