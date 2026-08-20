<?php

declare(strict_types=1);

use App\Livewire\Goals\Index;
use App\Models\User;
use App\Support\Money;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
});

it('lista as metas do protótipo', function (): void {
    Livewire::test(Index::class)
        ->assertOk()
        ->assertSee('Fundo de Emergência')
        ->assertSee('Viagem Europa')
        ->assertSee('Nova Meta')
        ->assertSee('concluído', escape: false);
});

it('calcula progresso e quanto falta', function (): void {
    $goal = Livewire::test(Index::class)->instance()->goals->firstWhere('id', 'g1');

    // 18.500 de 30.000
    expect($goal->percent())->toBeGreaterThan(61.0)->toBeLessThan(62.0)
        ->and($goal->remaining()->cents)->toBe(Money::fromReais(11500)->cents)
        ->and($goal->isCompleted())->toBeFalse();
});

it('registra um aporte na meta', function (): void {
    $component = Livewire::test(Index::class)
        ->call('startDeposit', 'g2')
        ->assertSet('depositing', 'g2')
        ->set('depositValue', '800')
        ->call('saveDeposit')
        ->assertSet('depositing', null);

    expect($component->instance()->goals->firstWhere('id', 'g2')->current_cents)
        ->toBe(Money::fromReais(5000)->cents);
});

it('não deixa o aporte passar do valor alvo', function (): void {
    $component = Livewire::test(Index::class)
        ->call('startDeposit', 'g3')
        ->set('depositValue', '999999')
        ->call('saveDeposit');

    $goal = $component->instance()->goals->firstWhere('id', 'g3');

    expect($goal->current_cents)->toBe($goal->target_cents)
        ->and($goal->isCompleted())->toBeTrue()
        ->and($goal->remaining()->isZero())->toBeTrue();
});

it('recusa aporte inválido', function (): void {
    Livewire::test(Index::class)
        ->call('startDeposit', 'g1')
        ->set('depositValue', '0')
        ->call('saveDeposit')
        ->assertHasErrors(['depositValue' => 'gt'])
        ->assertSet('depositing', 'g1');
});

it('cria uma meta pelo formulário', function (): void {
    $component = Livewire::test(Index::class)
        ->set('formIcon', '🏡')
        ->set('formName', 'Entrada do apê')
        ->set('formTarget', '60000')
        ->set('formCurrent', '1500')
        ->set('formDeadline', now()->addYear()->format('Y-m-d'))
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('formName', '');

    $created = $component->instance()->goals->firstWhere('name', 'Entrada do apê');

    expect($created)->not->toBeNull()
        ->and($created->icon)->toBe('🏡')
        ->and($created->target_cents)->toBe(Money::fromReais(60000)->cents)
        ->and($created->current_cents)->toBe(Money::fromReais(1500)->cents);
});

it('recusa meta com prazo no passado ou já guardado maior que o alvo', function (): void {
    Livewire::test(Index::class)
        ->set('formName', 'Meta ruim')
        ->set('formTarget', '100')
        ->set('formCurrent', '500')
        ->set('formDeadline', now()->subDay()->format('Y-m-d'))
        ->call('save')
        ->assertHasErrors(['formCurrent' => 'lte', 'formDeadline' => 'after']);
});

it('remove uma meta', function (): void {
    $component = Livewire::test(Index::class)->call('delete', 'g4');

    expect($component->instance()->goals->firstWhere('id', 'g4'))->toBeNull();
});
