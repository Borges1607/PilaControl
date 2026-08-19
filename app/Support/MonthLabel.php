<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Date;

/**
 * Rótulos de mês no formato do protótipo: "Ago/26" nos eixos e filtros,
 * "Agosto de 2026" nos títulos. Abreviações fixas em pt-BR de propósito —
 * são conteúdo do design, não interface traduzível.
 */
final class MonthLabel
{
    private const SHORT = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

    private const LONG = [
        'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
        'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro',
    ];

    /**
     * @param  CarbonInterface|string  $month  data ou chave "Y-m"
     */
    public static function short(CarbonInterface|string $month): string
    {
        [$year, $index] = self::parse($month);

        return self::SHORT[$index].'/'.substr((string) $year, -2);
    }

    /**
     * @param  CarbonInterface|string  $month  data ou chave "Y-m"
     */
    public static function long(CarbonInterface|string $month): string
    {
        [$year, $index] = self::parse($month);

        return self::LONG[$index].' de '.$year;
    }

    /**
     * Data completa no formato brasileiro: 18/08/2026.
     */
    public static function date(CarbonInterface $date): string
    {
        return $date->format('d/m/Y');
    }

    /**
     * Data com dia da semana, como no cabeçalho do protótipo: "ter, 19 ago 2026".
     */
    public static function weekdayDate(CarbonInterface $date): string
    {
        $weekdays = ['dom', 'seg', 'ter', 'qua', 'qui', 'sex', 'sáb'];

        return $weekdays[(int) $date->format('w')]
            .', '.$date->format('j')
            .' '.mb_strtolower(self::SHORT[(int) $date->format('n') - 1])
            .' '.$date->format('Y');
    }

    /**
     * Chave canônica de mês usada em filtros e orçamentos.
     */
    public static function key(CarbonInterface|string $month): string
    {
        [$year, $index] = self::parse($month);

        return $year.'-'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
    }

    public static function currentKey(): string
    {
        return Date::now()->format('Y-m');
    }

    /**
     * @return array{int, int} ano e índice do mês (0 a 11)
     */
    private static function parse(CarbonInterface|string $month): array
    {
        if ($month instanceof CarbonInterface) {
            return [(int) $month->format('Y'), (int) $month->format('n') - 1];
        }

        [$year, $number] = array_pad(explode('-', $month), 2, '1');

        return [(int) $year, (int) $number - 1];
    }
}
