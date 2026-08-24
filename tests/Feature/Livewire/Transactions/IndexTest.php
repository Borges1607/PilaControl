<?php

declare(strict_types=1);

use App\Enums\TransactionType;
use App\Livewire\Transactions\Index;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->user = User::factory()->create();

    $this->actingAs($this->user);

    // As categorias vêm do conjunto padrão da conta — ver CreateDefaultCategories.
    $this->moradia = $this->user->categories()->where('name', 'Moradia')->sole();
    $this->alimentacao = $this->user->categories()->where('name', 'Alimentação')->sole();
    $this->salario = $this->user->categories()->where('name', 'Salário')->sole();

    $this->aluguel = Transaction::factory()->for($this->moradia)->create([
        'user_id' => $this->user->id,
        'description' => 'Aluguel',
        'amount_cents' => 180_000,
        'type' => TransactionType::Expense,
        'date' => now()->startOfMonth(),
    ]);

    Transaction::factory()->for($this->alimentacao)->create([
        'user_id' => $this->user->id,
        'description' => 'Supermercado',
        'amount_cents' => 38_750,
        'type' => TransactionType::Expense,
        'date' => now()->startOfMonth()->addDays(5),
    ]);

    Transaction::factory()->for($this->salario)->create([
        'user_id' => $this->user->id,
        'description' => 'Salário do mês',
        'amount_cents' => 850_000,
        'type' => TransactionType::Income,
        'date' => now()->startOfMonth()->subMonth(),
    ]);
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

it('lista apenas os lançamentos do usuário', function (): void {
    $outro = User::factory()->create();

    Transaction::factory()->for(Category::factory()->for($outro))->create([
        'user_id' => $outro->id,
        'description' => 'Lançamento alheio',
    ]);

    $component = Livewire::test(Index::class)->assertDontSee('Lançamento alheio');

    expect($component->instance()->transactions)->toHaveCount(3);
});

it('mostra o mais recente primeiro', function (): void {
    $component = Livewire::test(Index::class);

    expect($component->instance()->transactions->first()->description)->toBe('Supermercado');
});

it('filtra por tipo', function (): void {
    $component = Livewire::test(Index::class)->call('setType', 'income');

    expect($component->instance()->transactions)->toHaveCount(1)
        ->each(fn ($tx) => $tx->type->toBe(TransactionType::Income));
});

it('ignora tipo desconhecido', function (): void {
    Livewire::test(Index::class)
        ->call('setType', 'qualquer')
        ->assertSet('type', 'all');
});

it('filtra por descrição', function (): void {
    $component = Livewire::test(Index::class)->set('search', 'aluguel');

    expect($component->instance()->transactions)->toHaveCount(1)
        ->each(fn ($tx) => $tx->description->toBe('Aluguel'));
});

it('filtra por categoria', function (): void {
    $component = Livewire::test(Index::class)->set('categoryId', (string) $this->alimentacao->id);

    expect($component->instance()->transactions)->toHaveCount(1)
        ->each(fn ($tx) => $tx->category_id->toBe($this->alimentacao->id));
});

it('filtra por mês', function (): void {
    $component = Livewire::test(Index::class)->set('month', now()->format('Y-m'));

    expect($component->instance()->transactions)->toHaveCount(2);

    $component->set('month', now()->subMonth()->format('Y-m'));

    expect($component->instance()->transactions)->toHaveCount(1);
});

it('lista só os meses que têm lançamento, do mais recente ao mais antigo', function (): void {
    $component = Livewire::test(Index::class);

    expect(array_keys($component->instance()->months))->toBe([
        now()->format('Y-m'),
        now()->subMonth()->format('Y-m'),
    ]);
});

it('ignora mês malformado vindo da URL', function (): void {
    $component = Livewire::test(Index::class)->set('month', 'abc');

    expect($component->instance()->transactions)->toHaveCount(3);
});

it('soma o recorte visível, não a base inteira', function (): void {
    $component = Livewire::test(Index::class)->set('month', now()->format('Y-m'));

    $totals = $component->instance()->totals;

    expect($totals->expense->cents)->toBe(218_750)
        ->and($totals->income->cents)->toBe(0)
        ->and($totals->balance->cents)->toBe(-218_750)
        ->and($totals->count)->toBe(2);
});

it('limpa todos os filtros', function (): void {
    Livewire::test(Index::class)
        ->set('search', 'aluguel')
        ->set('categoryId', (string) $this->alimentacao->id)
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

    $component->set('categoryId', (string) $this->alimentacao->id);

    expect($component->instance()->hasFilters)->toBeTrue();

    $component->call('clearFilters');

    expect($component->instance()->hasFilters)->toBeFalse();
});

it('grava a transação do formulário', function (): void {
    Livewire::test(Index::class)
        ->set('formType', 'expense')
        ->set('formDescription', 'Padaria da esquina')
        ->set('formAmount', '32.90')
        ->set('formDate', now()->format('Y-m-d'))
        ->set('formCategoryId', (string) $this->alimentacao->id)
        ->set('formNotes', 'pão de queijo')
        ->call('save')
        ->assertHasNoErrors()
        // O formulário volta ao estado inicial para o próximo lançamento.
        ->assertSet('formDescription', '')
        ->assertSet('formCategoryId', '');

    $criada = $this->user->transactions()->where('description', 'Padaria da esquina')->sole();

    expect($criada->amount_cents)->toBe(3_290)
        ->and($criada->category_id)->toBe($this->alimentacao->id)
        ->and($criada->type)->toBe(TransactionType::Expense)
        ->and($criada->notes)->toBe('pão de queijo');
});

it('só oferece categoria compatível com o tipo', function (): void {
    $component = Livewire::test(Index::class)->set('formType', 'income');

    expect($component->instance()->formCategories->contains($this->salario))->toBeTrue()
        ->and($component->instance()->formCategories->contains($this->moradia))->toBeFalse();
});

it('valida o formulário de nova transação', function (): void {
    Livewire::test(Index::class)
        ->set('formDescription', '')
        ->set('formAmount', '0,00')
        ->set('formCategoryId', '')
        ->call('save')
        ->assertHasErrors(['formDescription', 'formAmount', 'formCategoryId']);

    expect($this->user->transactions()->count())->toBe(3);
});

it('recusa categoria incompatível com o tipo', function (): void {
    Livewire::test(Index::class)
        ->set('formType', 'expense')
        ->set('formDescription', 'Bônus')
        ->set('formAmount', '100,00')
        ->set('formCategoryId', (string) $this->salario->id)
        ->call('save')
        ->assertHasErrors(['formCategoryId']);
});

it('recusa categoria de outro usuário', function (): void {
    $alheia = Category::factory()->create();

    Livewire::test(Index::class)
        ->set('formDescription', 'Tentativa')
        ->set('formAmount', '10,00')
        ->set('formCategoryId', (string) $alheia->id)
        ->call('save')
        ->assertHasErrors(['formCategoryId']);

    expect($this->user->transactions()->where('description', 'Tentativa')->exists())->toBeFalse();
});

it('remove uma transação', function (): void {
    Livewire::test(Index::class)->call('delete', $this->aluguel->id);

    expect(Transaction::query()->find($this->aluguel->id))->toBeNull();
});

it('não remove transação de outro usuário nem pela ação direta', function (): void {
    $outro = User::factory()->create();

    $alheia = Transaction::factory()->for(Category::factory()->for($outro))->create([
        'user_id' => $outro->id,
    ]);

    expect(fn (): mixed => Livewire::test(Index::class)->call('delete', $alheia->id))
        ->toThrow(ModelNotFoundException::class);

    expect(Transaction::query()->find($alheia->id))->not->toBeNull();
});

it('reparte o valor em parcelas iguais ao ligar o parcelamento', function (): void {
    $component = Livewire::test(Index::class)
        ->set('formAmount', '1.200,00')
        ->set('formInstallmentCount', '12')
        ->set('formInstallment', true);

    expect($component->get('formInstallments'))->toHaveCount(12)
        ->each(fn ($amount): mixed => $amount->toBe('100,00'));
});

it('joga o resto do rateio nas primeiras parcelas', function (): void {
    $component = Livewire::test(Index::class)
        ->set('formAmount', '10,00')
        ->set('formInstallmentCount', '3')
        ->set('formInstallment', true);

    expect($component->get('formInstallments'))->toBe(['3,34', '3,33', '3,33']);
});

it('redistribui ao mudar o número de parcelas', function (): void {
    $component = Livewire::test(Index::class)
        ->set('formAmount', '1.200,00')
        ->set('formInstallment', true)
        ->set('formInstallmentCount', '4');

    expect($component->get('formInstallments'))->toBe(['300,00', '300,00', '300,00', '300,00']);
});

it('prende o número de parcelas entre dois e sessenta', function (): void {
    $component = Livewire::test(Index::class)
        ->set('formAmount', '100,00')
        ->set('formInstallment', true);

    $component->set('formInstallmentCount', '1');
    expect($component->get('formInstallmentCount'))->toBe('2');

    $component->set('formInstallmentCount', '999');
    expect($component->get('formInstallmentCount'))->toBe('60');
});

it('reescreve o total quando uma parcela muda de valor', function (): void {
    $component = Livewire::test(Index::class)
        ->set('formAmount', '300,00')
        ->set('formInstallmentCount', '3')
        ->set('formInstallment', true)
        ->set('formInstallments.0', '150,00');

    expect($component->get('formAmount'))->toBe('350,00');
});

it('descarta as parcelas ao desligar o parcelamento', function (): void {
    $component = Livewire::test(Index::class)
        ->set('formAmount', '300,00')
        ->set('formInstallment', true)
        ->set('formInstallment', false);

    expect($component->get('formInstallments'))->toBe([]);
});

it('lê o valor pela máscara: os dois últimos dígitos são os centavos', function (): void {
    Livewire::test(Index::class)
        ->set('formDescription', 'Café')
        ->set('formCategoryId', (string) $this->alimentacao->id)
        ->set('formAmount', '1,11')
        ->call('save')
        ->assertHasNoErrors();

    expect($this->user->transactions()->where('description', 'Café')->sole()->amount_cents)
        ->toBe(111);
});

it('lança uma transação por mês ao salvar parcelado', function (): void {
    Livewire::test(Index::class)
        ->set('formType', 'expense')
        ->set('formDescription', 'Geladeira')
        ->set('formDate', '2026-01-31')
        ->set('formCategoryId', (string) $this->moradia->id)
        ->set('formAmount', '1.200,00')
        ->set('formInstallmentCount', '3')
        ->set('formInstallment', true)
        ->call('save')
        ->assertHasNoErrors();

    $parcelas = $this->user->transactions()
        ->whereLike('description', 'Geladeira%')
        ->orderBy('date')
        ->get();

    expect($parcelas)->toHaveCount(3)
        ->and($parcelas->pluck('description')->all())
        ->toBe(['Geladeira (1/3)', 'Geladeira (2/3)', 'Geladeira (3/3)'])
        ->and($parcelas->pluck('amount_cents')->all())->toBe([40000, 40000, 40000])
        ->and($parcelas->map(fn (Transaction $tx): string => $tx->date->format('Y-m-d'))->all())
        ->toBe(['2026-01-31', '2026-02-28', '2026-03-31']);
});

it('salva parcelas de valores diferentes', function (): void {
    Livewire::test(Index::class)
        ->set('formType', 'expense')
        ->set('formDescription', 'Notebook')
        ->set('formCategoryId', (string) $this->moradia->id)
        ->set('formAmount', '1.000,00')
        ->set('formInstallmentCount', '2')
        ->set('formInstallment', true)
        ->set('formInstallments.0', '700,00')
        ->set('formInstallments.1', '300,00')
        ->call('save')
        ->assertHasNoErrors();

    expect($this->user->transactions()->whereLike('description', 'Notebook%')->pluck('amount_cents')->all())
        ->toBe([70000, 30000]);
});

it('limpa o parcelamento depois de salvar', function (): void {
    $component = Livewire::test(Index::class)
        ->set('formDescription', 'Sofá')
        ->set('formCategoryId', (string) $this->moradia->id)
        ->set('formAmount', '600,00')
        ->set('formInstallmentCount', '2')
        ->set('formInstallment', true)
        ->call('save');

    expect($component->get('formInstallment'))->toBeFalse()
        ->and($component->get('formInstallments'))->toBe([]);
});

it('recusa parcela sem valor', function (): void {
    Livewire::test(Index::class)
        ->set('formDescription', 'TV')
        ->set('formCategoryId', (string) $this->moradia->id)
        ->set('formAmount', '600,00')
        ->set('formInstallmentCount', '2')
        ->set('formInstallment', true)
        ->set('formInstallments.1', '0,00')
        ->call('save')
        ->assertHasErrors(['formInstallments.1']);

    expect($this->user->transactions()->whereLike('description', 'TV%')->exists())->toBeFalse();
});

it('recusa descrição que não cabe com o sufixo da parcela', function (): void {
    Livewire::test(Index::class)
        ->set('formDescription', str_repeat('a', 250))
        ->set('formCategoryId', (string) $this->moradia->id)
        ->set('formAmount', '600,00')
        ->set('formInstallmentCount', '2')
        ->set('formInstallment', true)
        ->call('save')
        ->assertHasErrors(['formDescription']);
});

it('mostra o cronograma das parcelas mês a mês', function (): void {
    $component = Livewire::test(Index::class)
        ->set('formDate', '2026-11-10')
        ->set('formAmount', '300,00')
        ->set('formInstallmentCount', '3')
        ->set('formInstallment', true);

    expect(array_column($component->instance()->installmentRows, 'month'))
        ->toBe(['Nov/26', 'Dez/26', 'Jan/27']);
});

it('mostra os campos de parcela só com o parcelamento ligado', function (): void {
    Livewire::test(Index::class)
        ->assertSee('Parcelar em vários meses')
        ->assertDontSee('Número de parcelas')
        ->set('formAmount', '1.200,00')
        ->set('formInstallmentCount', '3')
        ->set('formInstallment', true)
        ->assertSee('Número de parcelas')
        ->assertSee('Valor total (R$)')
        ->assertSee('Total parcelado')
        ->assertSee('R$ 1.200,00')
        ->assertSee('Adicionar parcelas');
});
