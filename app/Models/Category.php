<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CategoryType;
use App\Enums\TransactionType;
use App\Policies\CategoryPolicy;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Gaveta de um lançamento. Sempre de um usuário — não existe categoria global.
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $icon
 * @property string $color
 * @property CategoryType $type
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read Collection<int, Transaction> $transactions
 * @property-read Collection<int, Budget> $budgets
 */
#[Fillable(['name', 'icon', 'color', 'type'])]
#[UsePolicy(CategoryPolicy::class)]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CategoryType::class,
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
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * @return HasMany<Budget, $this>
     */
    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    /**
     * A categoria aceita lançamentos do tipo informado? "Ambos" aceita os dois.
     */
    public function accepts(TransactionType $type): bool
    {
        return $this->type->accepts($type);
    }

    /**
     * Tem lançamento pendurado? Categoria em uso não se apaga — o histórico não
     * pode perder a gaveta. É a mesma regra do `restrictOnDelete` na migration,
     * checada antes para o usuário receber aviso em vez de erro de banco.
     */
    public function isInUse(): bool
    {
        return $this->transactions()->exists();
    }

    /**
     * Ordem de listagem: a de cadastro. Preserva a sequência do conjunto padrão
     * (receitas, depois despesas) e põe o que o usuário cria no fim.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    #[Scope]
    protected function inRegistryOrder(Builder $query): Builder
    {
        return $query->orderBy('id');
    }
}
