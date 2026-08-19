<?php

declare(strict_types=1);

namespace App\Support;

use Stringable;

/**
 * Valor monetário em centavos. Nunca usar float para dinheiro no domínio —
 * este objeto é a única porta de entrada e saída de valores na interface.
 */
final readonly class Money implements Stringable
{
    private function __construct(public int $cents) {}

    public static function fromCents(int $cents): self
    {
        return new self($cents);
    }

    /**
     * Só para dados vindos de fora do domínio (protótipo, import, formulário).
     */
    public static function fromReais(int|float|string $reais): self
    {
        return new self((int) round(((float) $reais) * 100));
    }

    public static function zero(): self
    {
        return new self(0);
    }

    /**
     * @param  iterable<self>  $values
     */
    public static function sum(iterable $values): self
    {
        $total = 0;

        foreach ($values as $value) {
            $total += $value->cents;
        }

        return new self($total);
    }

    public function plus(self $other): self
    {
        return new self($this->cents + $other->cents);
    }

    public function minus(self $other): self
    {
        return new self($this->cents - $other->cents);
    }

    public function abs(): self
    {
        return new self(abs($this->cents));
    }

    public function isNegative(): bool
    {
        return $this->cents < 0;
    }

    public function isZero(): bool
    {
        return $this->cents === 0;
    }

    /**
     * Percentual deste valor sobre outro, limitado ao teto informado.
     */
    public function percentOf(self $total, ?float $cap = 100.0): float
    {
        if ($total->cents === 0) {
            return 0.0;
        }

        $pct = ($this->cents / $total->cents) * 100;

        return $cap === null ? $pct : min($cap, $pct);
    }

    /**
     * "R$ 1.234,56". Com $sign, prefixa "+" ou "−" conforme o valor.
     */
    public function format(bool $sign = false): string
    {
        $prefix = '';

        if ($sign && $this->cents > 0) {
            $prefix = '+';
        } elseif ($this->cents < 0) {
            $prefix = '−';
        }

        return $prefix.'R$ '.number_format(abs($this->cents) / 100, 2, ',', '.');
    }

    /**
     * Forma compacta para eixos de gráfico: "R$ 1,2k".
     */
    public function short(): string
    {
        $reais = abs($this->cents) / 100;
        $prefix = $this->cents < 0 ? '−' : '';

        if ($reais >= 1000) {
            return $prefix.'R$ '.number_format($reais / 1000, 1, ',', '.').'k';
        }

        return $prefix.'R$ '.number_format($reais, 0, ',', '.');
    }

    /**
     * Só para serializar em JSON de gráfico — nunca para cálculo.
     */
    public function toReais(): float
    {
        return round($this->cents / 100, 2);
    }

    public function __toString(): string
    {
        return $this->format();
    }
}
