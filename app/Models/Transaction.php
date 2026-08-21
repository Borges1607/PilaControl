<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TransactionType;
use App\Support\Money;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Lançamento. Valor sempre positivo em centavos — o sinal vem do `type`.
 *
 * Só o mínimo por enquanto: existe para a categoria saber se está em uso e para
 * a FK `restrictOnDelete` ter model. Ganha Actions, factory e o resto quando a
 * tela de Transações entrar.
 *
 * @property int $id
 * @property int $user_id
 * @property int $category_id
 * @property Carbon $date
 * @property string $description
 * @property int $amount_cents
 * @property TransactionType $type
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read Category $category
 */
#[Fillable(['category_id', 'date', 'description', 'amount_cents', 'type', 'notes'])]
class Transaction extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'amount_cents' => 'integer',
            'type' => TransactionType::class,
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

    public function amount(): Money
    {
        return Money::fromCents($this->amount_cents);
    }

    /**
     * Chave "Y-m" usada em filtros e agrupamentos.
     */
    public function monthKey(): string
    {
        return $this->date->format('Y-m');
    }
}
