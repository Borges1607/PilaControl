<?php

declare(strict_types=1);

namespace App\Models;

use App\Policies\GoalPolicy;
use App\Support\Money;
use Database\Factories\GoalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;

/**
 * Objetivo de poupança: um alvo, quanto já foi guardado e um prazo.
 *
 * `current_cents` é campo, não soma de aportes — nenhuma tela mostra histórico.
 * Se um dia mostrar, entra `goal_contributions` e o valor passa a ser soma; como
 * `saved()` é a única leitura, a view não muda.
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $icon
 * @property int $target_cents
 * @property int $current_cents
 * @property Carbon $deadline
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 */
#[Fillable(['name', 'icon', 'target_cents', 'current_cents', 'deadline'])]
#[UsePolicy(GoalPolicy::class)]
class Goal extends Model
{
    /** @use HasFactory<GoalFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'target_cents' => 'integer',
            'current_cents' => 'integer',
            'deadline' => 'date',
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
     * O par de leituras do valor. Não se chamam `target()` e `saved()` como no
     * stand-in do protótipo porque `saved` é método estático do Eloquent — é o
     * registro do evento de gravação, e sobrescrever estoura no carregamento da
     * classe. O sufixo entrou nos dois para o par continuar lendo igual.
     */
    public function targetAmount(): Money
    {
        return Money::fromCents($this->target_cents);
    }

    public function savedAmount(): Money
    {
        return Money::fromCents($this->current_cents);
    }

    /**
     * Quanto falta guardar. Nunca negativo.
     */
    public function remaining(): Money
    {
        return Money::fromCents(max(0, $this->target_cents - $this->current_cents));
    }

    /**
     * Progresso em porcentagem, limitado a 100 — a barra não passa do fim.
     */
    public function percent(): float
    {
        return $this->savedAmount()->percentOf($this->targetAmount());
    }

    public function isCompleted(): bool
    {
        return $this->target_cents > 0 && $this->current_cents >= $this->target_cents;
    }

    /**
     * Dias até o prazo, arredondando para cima como o `daysUntil` do protótipo.
     * Zero ou negativo quando o prazo já passou.
     */
    public function daysRemaining(): int
    {
        return (int) ceil(Date::now()->diffInDays($this->deadline, false));
    }

    /**
     * Ordem da listagem: o prazo mais próximo primeiro — é o índice que a tabela
     * tem e a ordem que interessa a quem está poupando.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    #[Scope]
    protected function byDeadline(Builder $query): Builder
    {
        return $query->orderBy('deadline')->orderBy('id');
    }
}
