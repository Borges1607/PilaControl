<?php

declare(strict_types=1);

use App\Enums\CategoryType;
use App\Livewire\Categories\CategoriesModal;
use App\Models\User;
use App\Support\CategoryPresets;
use App\Support\Demo\Category;
use App\Support\DemoData;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
});

it('lista o registro de categorias', function (): void {
    Livewire::test(CategoriesModal::class)
        ->assertOk()
        ->assertSee('Nova Categoria')
        ->assertSee('Salário', escape: false)
        ->assertSee('Alimentação', escape: false)
        ->assertSee('padrão', escape: false);
});

it('oferece a base de ícones e de cores como atalho', function (): void {
    $component = Livewire::test(CategoriesModal::class)->assertSee('Sugestões', escape: false);

    // A primeira cor da paleta é a que o formulário já vem marcando.
    $component->assertSet('formColor', CategoryPresets::colors()[0]);

    // Escolher uma sugestão é só preencher o mesmo campo que aceita emoji digitado.
    $component->set('formIcon', CategoryPresets::icons()[0])
        ->set('formName', 'Salário extra')
        ->set('formType', 'income')
        ->call('save')
        ->assertHasNoErrors();

    $criada = $component->instance()->categories->first(fn (Category $c): bool => $c->name === 'Salário extra');

    expect($criada->icon)->toBe(CategoryPresets::icons()[0]);
});

it('abre na aba Todas com o registro inteiro', function (): void {
    $component = Livewire::test(CategoriesModal::class)->assertSet('tab', 'all');

    expect($component->instance()->visible)->toHaveCount(DemoData::categories()->count());
});

it('filtra por receita e por despesa', function (): void {
    $component = Livewire::test(CategoriesModal::class)->call('setTab', 'income');

    expect($component->instance()->visible->every(
        fn (Category $c): bool => $c->type === CategoryType::Income || $c->type === CategoryType::Both
    ))->toBeTrue();

    $component->call('setTab', 'expense');

    expect($component->instance()->visible->every(
        fn (Category $c): bool => $c->type === CategoryType::Expense || $c->type === CategoryType::Both
    ))->toBeTrue();
});

it('ignora aba desconhecida', function (): void {
    Livewire::test(CategoriesModal::class)
        ->call('setTab', 'qualquer')
        ->assertSet('tab', 'all');
});

it('cria uma categoria', function (): void {
    $component = Livewire::test(CategoriesModal::class)
        ->set('formIcon', '🐾')
        ->set('formName', 'Pet')
        ->set('formColor', '#26c6da')
        ->set('formType', 'expense')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('formName', '')
        // O tipo fica: quem cadastra várias seguidas costuma ficar no mesmo.
        ->assertSet('formType', 'expense');

    $criada = $component->instance()->categories->first(fn (Category $c): bool => $c->name === 'Pet');

    expect($criada)->not->toBeNull()
        ->and($criada->icon)->toBe('🐾')
        ->and($criada->color)->toBe('#26c6da')
        ->and($criada->type)->toBe(CategoryType::Expense);
});

it('recusa categoria sem nome ou com cor inválida', function (): void {
    Livewire::test(CategoriesModal::class)
        ->set('formName', '')
        ->set('formColor', 'azul')
        ->call('save')
        ->assertHasErrors(['formName' => 'required', 'formColor' => 'regex']);
});

it('remove categoria criada pelo usuário', function (): void {
    $component = Livewire::test(CategoriesModal::class)
        ->set('formName', 'Assinaturas')
        ->call('save');

    $id = $component->instance()->categories->first(fn (Category $c): bool => $c->name === 'Assinaturas')->id;

    $component->call('delete', $id);

    expect($component->instance()->categories->has($id))->toBeFalse();
});

it('não remove categoria padrão nem pela ação direta', function (): void {
    // A lista esconde o botão nas padrão, mas a ação é pública.
    $component = Livewire::test(CategoriesModal::class)->call('delete', 'food');

    expect($component->instance()->categories->has('food'))->toBeTrue()
        ->and($component->instance()->removed)->toBeEmpty();
});

it('marca como padrão só o que veio do registro', function (): void {
    $component = Livewire::test(CategoriesModal::class);

    expect($component->instance()->isDefault('salary'))->toBeTrue()
        ->and($component->instance()->isDefault('inventada'))->toBeFalse();
});
