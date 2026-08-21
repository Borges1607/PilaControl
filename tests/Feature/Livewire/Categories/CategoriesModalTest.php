<?php

declare(strict_types=1);

use App\Enums\CategoryType;
use App\Livewire\Categories\CategoriesModal;
use App\Models\Category;
use App\Models\User;
use App\Support\CategoryPresets;
use App\Support\DefaultCategories;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

beforeEach(function (): void {
    // A conta nasce com o conjunto padrão — é o `UserObserver` que cuida.
    $this->user = User::factory()->create();

    $this->actingAs($this->user);
});

it('lista o registro de categorias do usuário', function (): void {
    Livewire::test(CategoriesModal::class)
        ->assertOk()
        ->assertSee('Nova Categoria')
        ->assertSee('Salário', escape: false)
        ->assertSee('Alimentação', escape: false);
});

it('não mostra categoria de outro usuário', function (): void {
    Category::factory()->create([
        'user_id' => User::factory()->create()->id,
        'name' => 'Categoria alheia',
    ]);

    $component = Livewire::test(CategoriesModal::class)->assertDontSee('Categoria alheia');

    expect($component->instance()->categories)->toHaveCount(count(DefaultCategories::all()));
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

    expect($this->user->categories()->where('name', 'Salário extra')->value('icon'))
        ->toBe(CategoryPresets::icons()[0]);
});

it('abre na aba Todas com o registro inteiro', function (): void {
    $component = Livewire::test(CategoriesModal::class)->assertSet('tab', 'all');

    expect($component->instance()->visible)->toHaveCount(count(DefaultCategories::all()));
});

it('filtra por receita e por despesa', function (): void {
    Category::factory()->both()->create(['user_id' => $this->user->id, 'name' => 'Pix']);

    $component = Livewire::test(CategoriesModal::class)->call('setTab', 'income');

    expect($component->instance()->visible->every(
        fn (Category $c): bool => $c->type === CategoryType::Income || $c->type === CategoryType::Both
    ))->toBeTrue()
        // "Ambos" aparece nas duas abas.
        ->and($component->instance()->visible->contains('name', 'Pix'))->toBeTrue();

    $component->call('setTab', 'expense');

    expect($component->instance()->visible->every(
        fn (Category $c): bool => $c->type === CategoryType::Expense || $c->type === CategoryType::Both
    ))->toBeTrue()
        ->and($component->instance()->visible->contains('name', 'Pix'))->toBeTrue();
});

it('ignora aba desconhecida', function (): void {
    Livewire::test(CategoriesModal::class)
        ->call('setTab', 'qualquer')
        ->assertSet('tab', 'all');
});

it('cria uma categoria', function (): void {
    Livewire::test(CategoriesModal::class)
        ->set('formIcon', '🐾')
        ->set('formName', 'Pet')
        // Maiúsculas de propósito: o seletor nativo de cor devolve assim.
        ->set('formColor', '#26C6DA')
        ->set('formType', 'expense')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('formName', '')
        // O tipo fica: quem cadastra várias seguidas costuma ficar no mesmo.
        ->assertSet('formType', 'expense');

    $criada = $this->user->categories()->where('name', 'Pet')->sole();

    expect($criada->icon)->toBe('🐾')
        ->and($criada->color)->toBe('#26c6da')
        ->and($criada->type)->toBe(CategoryType::Expense);
});

it('recusa categoria sem nome ou com cor inválida', function (): void {
    Livewire::test(CategoriesModal::class)
        ->set('formName', '')
        ->set('formColor', 'azul')
        ->call('save')
        ->assertHasErrors(['formName' => 'required', 'formColor' => 'regex']);

    expect($this->user->categories()->count())->toBe(count(DefaultCategories::all()));
});

it('recusa nome repetido no mesmo tipo, mas aceita no outro', function (): void {
    Livewire::test(CategoriesModal::class)
        ->set('formName', 'Alimentação')
        ->set('formType', 'expense')
        ->call('save')
        ->assertHasErrors(['formName' => 'unique']);

    // Duas "Outros" convivem no registro padrão justamente por isso.
    Livewire::test(CategoriesModal::class)
        ->set('formName', 'Alimentação')
        ->set('formType', 'income')
        ->call('save')
        ->assertHasNoErrors();

    expect($this->user->categories()->where('name', 'Alimentação')->count())->toBe(2);
});

it('remove categoria sem lançamento', function (): void {
    $category = Category::factory()->create(['user_id' => $this->user->id, 'name' => 'Assinaturas']);

    Livewire::test(CategoriesModal::class)->call('delete', $category->id);

    expect(Category::query()->find($category->id))->toBeNull();
});

it('recusa remover categoria com lançamento', function (): void {
    $category = Category::factory()->create(['user_id' => $this->user->id]);

    $this->user->transactions()->create([
        'category_id' => $category->id,
        'date' => now(),
        'description' => 'Lançamento qualquer',
        'amount_cents' => 1000,
        'type' => 'expense',
    ]);

    $component = Livewire::test(CategoriesModal::class);

    expect($component->instance()->isInUse($category->id))->toBeTrue();

    $component->call('delete', $category->id);

    expect(Category::query()->find($category->id))->not->toBeNull();
});

it('não remove categoria de outro usuário nem pela ação direta', function (): void {
    // A listagem nem mostra, mas a ação é pública.
    $alheia = Category::factory()->create(['user_id' => User::factory()->create()->id]);

    // A busca sai da relação do usuário: id de fora simplesmente não existe.
    expect(fn (): mixed => Livewire::test(CategoriesModal::class)->call('delete', $alheia->id))
        ->toThrow(ModelNotFoundException::class);

    expect(Category::query()->find($alheia->id))->not->toBeNull();
});
