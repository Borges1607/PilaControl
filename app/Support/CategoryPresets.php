<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Atalhos oferecidos no cadastro de categoria.
 *
 * Não é dado de demonstração: sobrevive à criação da tabela `categories`, então
 * mora aqui e não no `DemoData`. O campo continua aceitando qualquer emoji e
 * qualquer cor — estas listas são só o caminho rápido.
 */
final class CategoryPresets
{
    /**
     * Cores do seletor (`PRESET_COLORS` do protótipo).
     *
     * @return array<int, string>
     */
    public static function colors(): array
    {
        return [
            '#00e676', '#29b6f6', '#ab47bc', '#ffca28', '#f85149', '#ff7043',
            '#ffa726', '#26a69a', '#42a5f5', '#ec407a', '#7e57c2', '#8d6e63',
            '#78909c', '#ef5350', '#66bb6a', '#26c6da',
        ];
    }

    /**
     * Ícones sugeridos, em três linhas de doze: as seis primeiras posições puxam
     * para receita, o resto cobre os gastos comuns de uma casa.
     *
     * @return array<int, string>
     */
    public static function icons(): array
    {
        return [
            '💼', '💻', '📈', '💰', '🏦', '🎁',
            '🏠', '🍽️', '🛒', '🚗', '⛽', '🚌',
            '🏥', '💊', '🏋️', '📚', '🎓', '💡',
            '📄', '📱', '🌐', '🎯', '🎮', '🎬',
            '✈️', '🛍️', '👕', '🐾', '💇', '☕',
            '🍺', '🎵', '🔧', '🎂', '💳', '📦',
        ];
    }
}
