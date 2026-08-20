<?php

declare(strict_types=1);

namespace App\Livewire\Categories;

use App\Enums\CategoryType;
use App\Support\CategoryPresets;
use App\Support\Demo\Category;
use App\Support\DemoData;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Cadastro de categorias. Mora no layout, aberto pelo botão no rodapé da sidebar.
 *
 * @property-read Collection<string, Category> $categories
 * @property-read Collection<string, Category> $visible
 */
class CategoriesModal extends Component
{
    /**
     * Categorias criadas e removidas nesta visita.
     *
     * Como as demais telas, o estado vive no componente enquanto a tabela
     * `categories` não existe — o que significa que uma categoria criada aqui
     * ainda não aparece na tela de Transações. Ao criar o model: apagar estas
     * duas propriedades e ligar a Actions\Categories\CreateCategory / DeleteCategory.
     *
     * @var array<int, array<string, string>>
     */
    public array $added = [];

    /**
     * @var array<int, string>
     */
    public array $removed = [];

    /**
     * Aba da listagem: "all", "income" ou "expense".
     */
    public string $tab = 'all';

    // Formulário de nova categoria.
    public string $formIcon = '📦';

    public string $formName = '';

    public string $formColor = '';

    public string $formType = 'expense';

    public function mount(): void
    {
        $this->formColor = CategoryPresets::colors()[0];
    }

    /**
     * @return Collection<string, Category>
     */
    #[Computed]
    public function categories(): Collection
    {
        return DemoData::categories()
            ->concat($this->addedCategories())
            ->reject(fn (Category $category): bool => in_array($category->id, $this->removed, true))
            ->keyBy(fn (Category $category): string => $category->id);
    }

    /**
     * Categorias da aba atual. "Ambos" aparece em todas.
     *
     * @return Collection<string, Category>
     */
    #[Computed]
    public function visible(): Collection
    {
        if ($this->tab === 'all') {
            return $this->categories;
        }

        return $this->categories->filter(
            fn (Category $category): bool => $category->type->value === $this->tab
                || $category->type === CategoryType::Both
        );
    }

    /**
     * Categoria que veio do registro padrão não pode ser removida.
     */
    public function isDefault(string $categoryId): bool
    {
        return DemoData::categories()->has($categoryId);
    }

    public function setTab(string $tab): void
    {
        if (! in_array($tab, ['all', 'income', 'expense'], true)) {
            return;
        }

        $this->tab = $tab;

        unset($this->visible);
    }

    public function save(): void
    {
        $validated = $this->validate([
            'formIcon' => ['required', 'string', 'max:8'],
            'formName' => ['required', 'string', 'max:40'],
            'formColor' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'formType' => ['required', 'in:income,expense,both'],
        ], attributes: [
            'formIcon' => 'ícone',
            'formName' => 'nome',
            'formColor' => 'cor',
            'formType' => 'tipo',
        ]);

        $this->added[] = [
            'id' => 'new-'.Str::slug($validated['formName']).'-'.Date::now()->getTimestamp(),
            'name' => trim($validated['formName']),
            'icon' => $validated['formIcon'],
            'color' => mb_strtolower($validated['formColor']),
            'type' => $validated['formType'],
        ];

        // O tipo é mantido: quem cadastra várias seguidas costuma ficar no mesmo.
        $this->formIcon = '📦';
        $this->formName = '';
        $this->formColor = CategoryPresets::colors()[0];

        $this->resetValidation();
        $this->forgetResults();

        Flux::toast(variant: 'success', text: 'Categoria criada.');
    }

    public function delete(string $id): void
    {
        // Guarda de servidor: a lista já esconde o botão nas padrão, mas a ação
        // é pública e não pode confiar só na view.
        if ($this->isDefault($id)) {
            return;
        }

        $this->removed[] = $id;
        $this->added = array_values(array_filter(
            $this->added,
            fn (array $row): bool => $row['id'] !== $id,
        ));

        $this->forgetResults();

        Flux::toast(variant: 'success', text: 'Categoria removida.');
    }

    /**
     * @return Collection<int, Category>
     */
    private function addedCategories(): Collection
    {
        return collect($this->added)->map(fn (array $row): Category => new Category(
            id: $row['id'],
            name: $row['name'],
            icon: $row['icon'],
            color: $row['color'],
            type: CategoryType::from($row['type']),
        ));
    }

    private function forgetResults(): void
    {
        unset($this->categories, $this->visible);
    }
}
