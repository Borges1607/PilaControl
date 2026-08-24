<?php

declare(strict_types=1);

use App\Livewire\Goals\Index as Goals;
use App\Livewire\Transactions\Index as Transactions;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
});

it('roda em pt_BR', function (): void {
    expect(app()->getLocale())->toBe('pt_BR');
});

it('devolve a mensagem de campo obrigatório em português', function (): void {
    $component = Livewire::test(Transactions::class)
        ->set('formDescription', '')
        ->call('save');

    // A concordância é com "campo", não com o nome do campo — ver o cabeçalho
    // de lang/pt_BR/validation.php.
    expect($component->errors()->first('formDescription'))
        ->toBe('O campo descrição é obrigatório.');
});

it('traduz as outras regras que os formulários usam', function (): void {
    $component = Livewire::test(Goals::class)
        ->set('formName', 'Viagem')
        ->set('formTarget', 'muito')
        ->call('save');

    expect($component->errors()->first('formTarget'))->toBe('O campo valor alvo deve ser um número.');

    $component = Livewire::test(Transactions::class)
        ->set('formDescription', 'Padaria')
        // Preenchido, mas não é categoria dele: cai na regra `in`, não na `required`.
        ->set('formCategoryId', '999999')
        ->call('save');

    expect($component->errors()->first('formCategoryId'))
        ->toBe('O valor selecionado para categoria é inválido.');
});

it('nomeia o campo na recusa do valor com máscara', function (): void {
    $component = Livewire::test(Transactions::class)
        ->set('formDescription', 'Padaria')
        ->set('formAmount', '0,00')
        ->call('save');

    expect($component->errors()->first('formAmount'))->toBe('O campo valor deve ser maior que zero.');
});

it('traduz também as regras de data e de comparação', function (): void {
    $component = Livewire::test(Goals::class)
        ->set('formName', 'Meta ruim')
        ->set('formTarget', '100')
        ->set('formCurrent', '500')
        ->set('formDeadline', now()->subDay()->format('Y-m-d'))
        ->call('save');

    expect($component->errors()->first('formDeadline'))
        ->toContain('deve ser uma data posterior')
        ->and($component->errors()->first('formCurrent'))
        ->toBe('O campo valor já guardado deve ser menor ou igual a 100.');
});

it('traduz a recusa de login do Fortify', function (): void {
    auth()->logout();

    $this->post(route('login.store'), [
        'email' => 'ninguem@pilacontrol.test',
        'password' => 'errada',
    ])->assertSessionHasErrors(['email' => 'E-mail ou senha incorretos.']);
});
