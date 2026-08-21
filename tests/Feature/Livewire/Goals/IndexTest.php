<?php

declare(strict_types=1);

use App\Livewire\Goals\Index;
use App\Models\Goal;
use App\Models\User;
use App\Support\Money;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->user = User::factory()->create();

    $this->actingAs($this->user);

    $this->emergencia = Goal::factory()->for($this->user)
        ->target(30_000)->saved(18_500)
        ->dueOn(now()->addMonths(6)->format('Y-m-d'))
        ->create(['name' => 'Fundo de Emergência', 'icon' => '🛡️']);

    $this->viagem = Goal::factory()->for($this->user)
        ->target(15_000)->saved(4_200)
        ->dueOn(now()->addYear()->format('Y-m-d'))
        ->create(['name' => 'Viagem Europa', 'icon' => '✈️']);
});

it('lista as metas do usuário', function (): void {
    Livewire::test(Index::class)
        ->assertOk()
        ->assertSee('Fundo de Emergência', escape: false)
        ->assertSee('Viagem Europa')
        ->assertSee('Nova Meta')
        ->assertSee('concluído', escape: false);
});

it('mostra o prazo mais próximo primeiro', function (): void {
    $component = Livewire::test(Index::class);

    expect($component->instance()->goals->pluck('name')->all())
        ->toBe(['Fundo de Emergência', 'Viagem Europa']);
});

it('não mostra meta de outro usuário', function (): void {
    Goal::factory()->create(['name' => 'Meta alheia']);

    $component = Livewire::test(Index::class)->assertDontSee('Meta alheia');

    expect($component->instance()->goals)->toHaveCount(2);
});

it('calcula progresso e quanto falta', function (): void {
    $goal = Livewire::test(Index::class)->instance()->goals
        ->firstWhere('id', $this->emergencia->id);

    // 18.500 de 30.000
    expect($goal->percent())->toBeGreaterThan(61.0)->toBeLessThan(62.0)
        ->and($goal->remaining()->cents)->toBe(Money::fromReais(11_500)->cents)
        ->and($goal->isCompleted())->toBeFalse();
});

it('registra um aporte na meta', function (): void {
    Livewire::test(Index::class)
        ->call('startDeposit', $this->viagem->id)
        ->assertSet('depositing', $this->viagem->id)
        ->set('depositValue', '800')
        ->call('saveDeposit')
        ->assertSet('depositing', null)
        ->assertSet('depositValue', '');

    expect($this->viagem->refresh()->current_cents)->toBe(Money::fromReais(5_000)->cents);
});

it('não deixa o aporte passar do valor alvo', function (): void {
    Livewire::test(Index::class)
        ->call('startDeposit', $this->viagem->id)
        ->set('depositValue', '999999')
        ->call('saveDeposit');

    $goal = $this->viagem->refresh();

    expect($goal->current_cents)->toBe($goal->target_cents)
        ->and($goal->isCompleted())->toBeTrue()
        ->and($goal->remaining()->isZero())->toBeTrue();
});

it('recusa aporte inválido', function (): void {
    Livewire::test(Index::class)
        ->call('startDeposit', $this->emergencia->id)
        ->set('depositValue', '0')
        ->call('saveDeposit')
        ->assertHasErrors(['depositValue' => 'gt'])
        ->assertSet('depositing', $this->emergencia->id);

    expect($this->emergencia->refresh()->current_cents)->toBe(Money::fromReais(18_500)->cents);
});

it('cria uma meta pelo formulário', function (): void {
    Livewire::test(Index::class)
        ->set('formIcon', '🏡')
        ->set('formName', 'Entrada do apê')
        ->set('formTarget', '60000')
        ->set('formCurrent', '1500')
        ->set('formDeadline', now()->addYear()->format('Y-m-d'))
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('formName', '');

    $criada = $this->user->goals()->where('name', 'Entrada do apê')->sole();

    expect($criada->icon)->toBe('🏡')
        ->and($criada->target_cents)->toBe(Money::fromReais(60_000)->cents)
        ->and($criada->current_cents)->toBe(Money::fromReais(1_500)->cents)
        ->and($criada->deadline->format('H:i:s'))->toBe('00:00:00');
});

it('cria meta sem valor já guardado', function (): void {
    Livewire::test(Index::class)
        ->set('formName', 'Notebook')
        ->set('formTarget', '6000')
        ->set('formDeadline', now()->addMonths(3)->format('Y-m-d'))
        ->call('save')
        ->assertHasNoErrors();

    expect($this->user->goals()->where('name', 'Notebook')->value('current_cents'))->toBe(0);
});

it('recusa meta com prazo no passado ou já guardado maior que o alvo', function (): void {
    Livewire::test(Index::class)
        ->set('formName', 'Meta ruim')
        ->set('formTarget', '100')
        ->set('formCurrent', '500')
        ->set('formDeadline', now()->subDay()->format('Y-m-d'))
        ->call('save')
        ->assertHasErrors(['formCurrent' => 'lte', 'formDeadline' => 'after']);

    expect($this->user->goals()->count())->toBe(2);
});

it('remove uma meta', function (): void {
    Livewire::test(Index::class)->call('delete', $this->viagem->id);

    expect(Goal::query()->find($this->viagem->id))->toBeNull();
});

it('fecha o aporte aberto da meta removida', function (): void {
    Livewire::test(Index::class)
        ->call('startDeposit', $this->viagem->id)
        ->call('delete', $this->viagem->id)
        ->assertSet('depositing', null);
});

it('não mexe em meta de outro usuário nem pela ação direta', function (): void {
    $alheia = Goal::factory()->create();

    expect(fn (): mixed => Livewire::test(Index::class)->call('delete', $alheia->id))
        ->toThrow(ModelNotFoundException::class);

    expect(Goal::query()->find($alheia->id))->not->toBeNull();
});
