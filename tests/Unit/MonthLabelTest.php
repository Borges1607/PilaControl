<?php

declare(strict_types=1);

use App\Support\MonthLabel;
use Illuminate\Support\Facades\Date;

it('abrevia o mês como no protótipo', function (): void {
    expect(MonthLabel::short('2026-08'))->toBe('Ago/26')
        ->and(MonthLabel::short('2026-01'))->toBe('Jan/26')
        ->and(MonthLabel::short(Date::parse('2025-12-31')))->toBe('Dez/25');
});

it('escreve o mês por extenso', function (): void {
    expect(MonthLabel::long('2026-03'))->toBe('Março de 2026');
});

it('normaliza a chave do mês', function (): void {
    expect(MonthLabel::key('2026-3'))->toBe('2026-03')
        ->and(MonthLabel::key(Date::parse('2026-11-05')))->toBe('2026-11');
});

it('formata data no padrão brasileiro', function (): void {
    expect(MonthLabel::date(Date::parse('2026-08-18')))->toBe('18/08/2026');
});
