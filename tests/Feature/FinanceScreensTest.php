<?php

declare(strict_types=1);

use App\Models\User;

it('redireciona visitante nas telas do domínio financeiro', function (string $route): void {
    $this->get(route($route))->assertRedirect(route('login'));
})->with(['dashboard', 'transactions.index', 'budgets.index', 'goals.index', 'reports.index']);

it('renderiza a página completa para o usuário logado', function (string $route, string $heading): void {
    $this->actingAs(User::factory()->create());

    $this->get(route($route))
        ->assertOk()
        ->assertSee($heading, escape: false)
        ->assertSee('Nova Transação')
        ->assertSee('Relatórios');
})->with([
    ['dashboard', 'Dashboard'],
    ['transactions.index', 'Transações'],
    ['budgets.index', 'Orçamento'],
    ['goals.index', 'Metas'],
    ['reports.index', 'Relatórios'],
]);
