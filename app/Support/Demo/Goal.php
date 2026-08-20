<?php

declare(strict_types=1);

namespace App\Support\Demo;

use App\Support\Money;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Date;

/**
 * Substituto provisório do model Goal. Mesmas colunas da tabela `goals`.
 *
 * `current_cents` é campo aqui porque o protótipo trata assim. Se virar histórico
 * de aportes (`goal_contributions`), o valor passa a ser uma soma — os métodos
 * abaixo continuam sendo a única leitura, então a view não muda.
 */
final readonly class Goal
{
    public function __construct(
        public string $id,
        public string $name,
        public string $icon,
        public int $target_cents,
        public int $current_cents,
        public CarbonInterface $deadline,
    ) {}

    public function target(): Money
    {
        return Money::fromCents($this->target_cents);
    }

    public function saved(): Money
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
        return $this->saved()->percentOf($this->target());
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
}
