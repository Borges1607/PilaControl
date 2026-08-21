<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Money;
use Database\Factories\BudgetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Limite de gasto de uma categoria em um mês. Um por (usuário, categoria, mês).
 *
 * Não existe linha de limite zero: "sem limite" é a ausência do registro. Ver
 * `Actions\Budgets\RemoveCategoryBudget`.
 *
 * `user_id` não é preenchível, como no `Transaction`: quem cria é a Action, que
 * o tira da categoria.
 *
 * @property int $id
 * @property int $user_id
 * @property int $category_id
 * @property string $month
 * @property int $limit_cents
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read Category $category
 */
#[Fillable(['category_id', 'month', 'limit_cents'])]
class Budget extends Model
{
    /** @use HasFactory<BudgetFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'limit_cents' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function limit(): Money
    {
        return Money::fromCents($this->limit_cents);
    }

    /**
     * Limites de um mês pela chave "Y-m" — a mesma de `MonthLabel::key()`.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    #[Scope]
    protected function forMonth(Builder $query, string $month): Builder
    {
        return $query->where('month', $month);
    }
}
