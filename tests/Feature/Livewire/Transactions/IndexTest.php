<?php

declare(strict_types=1);

use App\Livewire\Transactions\Index;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
});

it('renderiza filtros, totais e a tabela', function (): void {
    Livewire::test(Index::class)
        ->assertOk()
        ->assertSee('Todas')
        ->assertSee('Receitas')
        ->assertSee('Despesas')
        ->assertSee('Todas categorias')
        ->assertSee('Todos os meses')
        ->assertSee('Aluguel');
});

it('filtra por tipo', function (): void {
    $component = Livewire::test(Index::class)->call('setType', 'income');

    expect($component->instance()->transactions)
        ->each(fn ($tx) => $tx->type->value->toBe('income'));
});

it('filtra por descrição', function (): void {
    $component = Livewire::test(Index::class)->set('search', 'aluguel');

    expect($component->instance()->transactions)->not->toBeEmpty()
        ->each(fn ($tx) => $tx->description->toContain('Aluguel'));
});

it('filtra por categoria', function (): void {
    $component = Livewire::test(Index::class)->set('categoryId', 'food');

    expect($component->instance()->transactions)->not->toBeEmpty()
        ->each(fn ($tx) => $tx->category_id->toBe('food'));
});

it('limpa todos os filtros', function (): void {
    Livewire::test(Index::class)
        ->set('search', 'aluguel')
        ->set('categoryId', 'food')
        ->set('month', now()->format('Y-m'))
        ->call('setType', 'income')
        ->call('clearFilters')
        ->assertSet('type', 'all')
        ->assertSet('search', '')
        ->assertSet('categoryId', '')
        ->assertSet('month', '');
});

it('sabe quando há filtro ativo', function (): void {
    $component = Livewire::test(Index::class);

    expect($component->instance()->hasFilters)->toBeFalse();

    $component->set('categoryId', 'food');

    expect($component->instance()->hasFilters)->toBeTrue();

    $component->call('clearFilters');

    expect($component->instance()->hasFilters)->toBeFalse();
});

it('adiciona uma transação pelo formulário', function (): void {
    $component = Livewire::test(Index::class)
        ->set('formType', 'expense')
        ->set('formDescription', 'Padaria da esquina')
        ->set('formAmount', '32.90')
        ->set('formCategoryId', 'food')
        ->call('save')
        ->assertHasNoErrors();

    expect($component->instance()->all->pluck('description'))->toContain('Padaria da esquina');
});

it('valida o formulário de nova transação', function (): void {
    Livewire::test(Index::class)
        ->set('formDescription', '')
        ->set('formAmount', '0')
        ->set('formCategoryId', '')
        ->call('save')
        ->assertHasErrors(['formDescription', 'formAmount', 'formCategoryId']);
});

it('recusa categoria incompatível com o tipo', function (): void {
    Livewire::test(Index::class)
        ->set('formType', 'expense')
        ->set('formDescription', 'Bônus')
        ->set('formAmount', '100')
        ->set('formCategoryId', 'salary')
        ->call('save')
        ->assertHasErrors(['formCategoryId']);
});

it('remove uma transação da listagem', function (): void {
    $component = Livewire::test(Index::class)->call('delete', 't2');

    expect($component->instance()->all->pluck('id'))->not->toContain('t2');
});
