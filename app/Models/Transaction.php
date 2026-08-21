<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TransactionType;
use App\Policies\TransactionPolicy;
use App\Support\Money;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Lançamento. Valor sempre positivo em centavos — o sinal vem do `type`.
 *
 * `user_id` não é preenchível: quem cria é a Action, que o tira da categoria.
 * Um lançamento é do mesmo dono da gaveta em que entra.
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
#[UsePolicy(TransactionPolicy::class)]
class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory;

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

    /**
     * Ordem da listagem: data descendente e, no empate do dia, o mais recente
     * primeiro. É a mesma ordem que o protótipo mostra.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    #[Scope]
    protected function latestFirst(Builder $query): Builder
    {
        return $query->orderByDesc('date')->orderByDesc('id');
    }

    /**
     * Recorte de um mês pela chave "Y-m" — a mesma que `MonthLabel::key()` dá.
     *
     * Intervalo de datas em vez de `strftime`: a coluna é indexada e a consulta
     * não fica presa ao dialeto do SQLite.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    #[Scope]
    protected function inMonth(Builder $query, string $month): Builder
    {
        $start = Carbon::createFromFormat('Y-m-d', $month.'-01')->startOfMonth();

        return $query->whereBetween('date', [$start, $start->copy()->endOfMonth()]);
    }
}
