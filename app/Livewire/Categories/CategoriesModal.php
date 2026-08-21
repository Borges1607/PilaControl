<?php

declare(strict_types=1);

namespace App\Livewire\Categories;

use App\Actions\Categories\CreateCategory;
use App\Actions\Categories\DeleteCategory;
use App\Enums\CategoryType;
use App\Exceptions\CategoryInUse;
use App\Models\Category;
use App\Support\CategoryPresets;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Cadastro de categorias. Mora no layout, aberto pelo botão no rodapé da sidebar.
 *
 * @property-read Collection<int, Category> $categories
 * @property-read Collection<int, Category> $visible
 * @property-read array<int, bool> $usage
 */
class CategoriesModal extends Component
{
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
     * O registro do usuário, indexado pelo id.
     *
     * @return Collection<int, Category>
     */
    #[Computed]
    public function categories(): Collection
    {
        return Auth::user()->categories()->inRegistryOrder()->get()->keyBy('id');
    }

    /**
     * Categorias da aba atual. "Ambos" aparece em todas.
     *
     * @return Collection<int, Category>
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
     * Quais categorias têm lançamento, em uma consulta só — a listagem precisa
     * saber de todas para decidir quem mostra botão de remover.
     *
     * @return array<int, bool>
     */
    #[Computed]
    public function usage(): array
    {
        return Auth::user()->transactions()
            ->distinct()
            ->pluck('category_id')
            ->mapWithKeys(fn (int $id): array => [$id => true])
            ->all();
    }

    /**
     * Categoria em uso não se apaga: o histórico não pode perder a gaveta.
     */
    public function isInUse(int $categoryId): bool
    {
        return isset($this->usage[$categoryId]);
    }

    public function setTab(string $tab): void
    {
        if (! in_array($tab, ['all', 'income', 'expense'], true)) {
            return;
        }

        $this->tab = $tab;

        unset($this->visible);
    }

    public function save(CreateCategory $createCategory): void
    {
        $validated = $this->validate([
            'formIcon' => ['required', 'string', 'max:8'],
            'formName' => [
                'required', 'string', 'max:40',
                // Duas "Outros" convivem porque diferem no tipo — a chave única
                // da tabela é (user_id, type, name), e a regra a espelha.
                Rule::unique('categories', 'name')
                    ->where('user_id', Auth::id())
                    ->where('type', $this->formType),
            ],
            'formColor' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'formType' => ['required', 'in:income,expense,both'],
        ], messages: [
            'formName.unique' => 'Já existe uma categoria :attribute deste tipo.',
        ], attributes: [
            'formIcon' => 'ícone',
            'formName' => 'nome',
            'formColor' => 'cor',
            'formType' => 'tipo',
        ]);

        $createCategory->handle(
            user: Auth::user(),
            name: $validated['formName'],
            icon: $validated['formIcon'],
            color: $validated['formColor'],
            type: CategoryType::from($validated['formType']),
        );

        // O tipo é mantido: quem cadastra várias seguidas costuma ficar no mesmo.
        $this->formIcon = '📦';
        $this->formName = '';
        $this->formColor = CategoryPresets::colors()[0];

        $this->resetValidation();
        $this->forgetResults();

        Flux::toast(variant: 'success', text: 'Categoria criada.');
    }

    public function delete(int $id, DeleteCategory $deleteCategory): void
    {
        // A listagem já esconde o botão nas que estão em uso, mas a ação é
        // pública: a busca sai da relação do usuário e a policy confirma.
        $category = Auth::user()->categories()->findOrFail($id);

        $this->authorize('delete', $category);

        try {
            $deleteCategory->handle($category);
        } catch (CategoryInUse $exception) {
            Flux::toast(variant: 'danger', text: $exception->getMessage());

            return;
        }

        $this->forgetResults();

        Flux::toast(variant: 'success', text: 'Categoria removida.');
    }

    private function forgetResults(): void
    {
        unset($this->categories, $this->visible, $this->usage);
    }
}
