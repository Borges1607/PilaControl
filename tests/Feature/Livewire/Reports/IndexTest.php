<?php

declare(strict_types=1);

use App\Livewire\Reports\Index;
use App\Models\User;
use App\Queries\Results\CategorySpending;
use App\Queries\Results\MonthPoint;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
});

it('lista os quatro painéis do protótipo', function (): void {
    Livewire::test(Index::class)
        ->assertOk()
        ->assertSee('Receitas vs Despesas')
        ->assertSee('Evolução do Saldo', escape: false)
        ->assertSee('Despesas por Categoria')
        ->assertSee('Ranking de Gastos')
        ->assertSee('3 meses')
        ->assertSee('6 meses')
        ->assertSee('12 meses');
});

it('abre em seis meses', function (): void {
    $component = Livewire::test(Index::class)->assertSet('period', 6);

    expect($component->instance()->since)
        ->toBe(now()->startOfMonth()->subMonths(5)->format('Y-m'));
});

it('recorta a série ao trocar o período', function (): void {
    $component = Livewire::test(Index::class)->call('setPeriod', 3);

    $since = $component->instance()->since;

    expect($since)->toBe(now()->startOfMonth()->subMonths(2)->format('Y-m'))
        ->and($component->instance()->timeline->every(
            fn (MonthPoint $point): bool => $point->month >= $since
        ))->toBeTrue();
});

it('ignora período fora da lista', function (): void {
    Livewire::test(Index::class)
        ->call('setPeriod', 99)
        ->assertSet('period', 6);
});

it('ordena o ranking do maior gasto para o menor', function (): void {
    $ranking = Livewire::test(Index::class)->instance()->ranking;

    $totals = $ranking->map(fn (CategorySpending $row): int => $row->total->cents)->all();

    expect($ranking)->not->toBeEmpty()
        ->and($totals)->toBe(collect($totals)->sortDesc()->values()->all());
});

it('soma as despesas do período no total', function (): void {
    $component = Livewire::test(Index::class);

    $somaDoRanking = (int) $component->instance()->ranking
        ->sum(fn (CategorySpending $row): int => $row->total->cents);

    expect($component->instance()->totalExpense->cents)->toBe($somaDoRanking);
});

it('distribui as fatias sobre o total do período', function (): void {
    $ranking = Livewire::test(Index::class)->instance()->ranking;

    $soma = $ranking->sum(fn (CategorySpending $row): float => $row->share);

    expect($soma)->toBeGreaterThan(99.0)->toBeLessThan(101.0);
});

it('mantém o período na URL', function (): void {
    Livewire::withQueryParams(['periodo' => 12])
        ->test(Index::class)
        ->assertSet('period', 12);
});

it('monta os três gráficos com séries do mesmo tamanho dos rótulos', function (): void {
    $charts = Livewire::test(Index::class)->instance()->charts;

    expect(array_keys($charts))->toBe(['receitas-despesas', 'evolucao-saldo', 'despesas-categoria']);

    foreach ($charts as $name => $payload) {
        foreach ($payload['series'] as $set) {
            expect($set['data'])->toHaveCount(count($payload['labels']), "série de {$name}");
        }
    }
});

it('avisa cada gráfico ao trocar o período', function (): void {
    // Os canvas ficam sob wire:ignore: sem este evento eles guardariam dados velhos.
    Livewire::test(Index::class)
        ->call('setPeriod', 3)
        ->assertDispatched('chart:data', name: 'receitas-despesas')
        ->assertDispatched('chart:data', name: 'evolucao-saldo')
        ->assertDispatched('chart:data', name: 'despesas-categoria');
});

it('não avisa os gráficos quando o período é inválido', function (): void {
    Livewire::test(Index::class)
        ->call('setPeriod', 99)
        ->assertNotDispatched('chart:data');
});
