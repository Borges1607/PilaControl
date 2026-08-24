<?php

declare(strict_types=1);

use App\Support\Money;

it('formata em reais no padrão brasileiro', function (): void {
    expect(Money::fromCents(123456)->format())->toBe('R$ 1.234,56')
        ->and(Money::fromCents(0)->format())->toBe('R$ 0,00')
        ->and(Money::fromCents(-50000)->format())->toBe('−R$ 500,00');
});

it('prefixa o sinal quando pedido', function (): void {
    expect(Money::fromCents(50000)->format(sign: true))->toBe('+R$ 500,00')
        ->and(Money::fromCents(-50000)->format(sign: true))->toBe('−R$ 500,00')
        ->and(Money::fromCents(0)->format(sign: true))->toBe('R$ 0,00');
});

it('converte reais em centavos sem erro de ponto flutuante', function (): void {
    expect(Money::fromReais(387.50)->cents)->toBe(38750)
        ->and(Money::fromReais('0.07')->cents)->toBe(7)
        ->and(Money::fromReais(1234.565)->cents)->toBe(123457);
});

it('encurta valores para o eixo do gráfico', function (): void {
    expect(Money::fromReais(8500)->short())->toBe('R$ 8,5k')
        ->and(Money::fromReais(999)->short())->toBe('R$ 999')
        ->and(Money::fromReais(1000)->short())->toBe('R$ 1,0k');
});

it('soma e subtrai preservando centavos', function (): void {
    $total = Money::fromReais(10.10)->plus(Money::fromReais(0.20))->minus(Money::fromReais(0.30));

    expect($total->cents)->toBe(1000);
});

it('calcula percentual limitado a cem', function (): void {
    expect(Money::fromReais(50)->percentOf(Money::fromReais(200)))->toBe(25.0)
        ->and(Money::fromReais(300)->percentOf(Money::fromReais(200)))->toBe(100.0)
        ->and(Money::fromReais(300)->percentOf(Money::fromReais(200), cap: null))->toBe(150.0)
        ->and(Money::fromReais(50)->percentOf(Money::zero()))->toBe(0.0);
});

it('reparte o valor em parcelas que somam o original', function (): void {
    $parts = Money::fromReais(10)->split(3);

    expect(array_map(fn (Money $part): int => $part->cents, $parts))->toBe([334, 333, 333])
        ->and(Money::sum($parts)->cents)->toBe(1000);
});

it('reparte em partes iguais quando a divisão é exata', function (): void {
    $parts = Money::fromReais(1200)->split(12);

    expect($parts)->toHaveCount(12)
        ->each(fn ($part): mixed => $part->cents->toBe(10000));
});

it('reparte valor negativo sem perder centavo', function (): void {
    $parts = Money::fromCents(-1000)->split(3);

    expect(array_map(fn (Money $part): int => $part->cents, $parts))->toBe([-334, -333, -333])
        ->and(Money::sum($parts)->cents)->toBe(-1000);
});

it('recusa repartir em menos de uma parte', function (): void {
    expect(fn (): mixed => Money::fromReais(10)->split(0))
        ->toThrow(InvalidArgumentException::class);
});

it('lê o campo com máscara tratando os dois últimos dígitos como centavos', function (): void {
    expect(Money::fromInput('1.234,56')->cents)->toBe(123456)
        ->and(Money::fromInput('0,01')->cents)->toBe(1)
        ->and(Money::fromInput('1,11')->cents)->toBe(111)
        ->and(Money::fromInput('111,00')->cents)->toBe(11100)
        ->and(Money::fromInput('R$ 1.234,56')->cents)->toBe(123456)
        ->and(Money::fromInput('')->cents)->toBe(0)
        ->and(Money::fromInput(null)->cents)->toBe(0)
        ->and(Money::fromInput('qualquer coisa')->cents)->toBe(0);
});

it('escreve de volta no formato do campo, sem R$ e sem sinal', function (): void {
    expect(Money::fromCents(123456)->toInput())->toBe('1.234,56')
        ->and(Money::fromCents(1)->toInput())->toBe('0,01')
        ->and(Money::zero()->toInput())->toBe('0,00')
        ->and(Money::fromCents(-50000)->toInput())->toBe('500,00');
});

it('faz a volta completa entre campo e valor', function (): void {
    foreach (['0,01', '0,11', '1,11', '11,10', '111,00', '1.234,56'] as $typed) {
        expect(Money::fromInput($typed)->toInput())->toBe($typed);
    }
});
