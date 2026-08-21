<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\CategoryType;

/**
 * O conjunto com que uma conta nasce — as treze categorias do protótipo.
 *
 * Não é dado de vitrine: sai do `DemoData` e sobrevive a ele, porque toda conta
 * nova recebe estas linhas de verdade na tabela `categories`. A partir daí são
 * categorias do usuário como qualquer outra: ele renomeia, apaga, cria as suas.
 *
 * A ordem importa — é a ordem de cadastro, e portanto a ordem de listagem:
 * receitas primeiro, depois as despesas.
 */
final class DefaultCategories
{
    /**
     * @return list<array{name: string, icon: string, color: string, type: CategoryType}>
     */
    public static function all(): array
    {
        return [
            self::row('Salário', '💼', '#00e676', CategoryType::Income),
            self::row('Freelance', '💻', '#29b6f6', CategoryType::Income),
            self::row('Investimentos', '📈', '#ab47bc', CategoryType::Income),
            self::row('Outros', '💰', '#ffca28', CategoryType::Income),
            self::row('Moradia', '🏠', '#f85149', CategoryType::Expense),
            self::row('Alimentação', '🍽️', '#ff7043', CategoryType::Expense),
            self::row('Transporte', '🚗', '#ffa726', CategoryType::Expense),
            self::row('Saúde', '🏥', '#26a69a', CategoryType::Expense),
            self::row('Educação', '📚', '#42a5f5', CategoryType::Expense),
            self::row('Lazer', '🎯', '#ec407a', CategoryType::Expense),
            self::row('Compras', '🛍️', '#7e57c2', CategoryType::Expense),
            self::row('Contas', '📄', '#8d6e63', CategoryType::Expense),
            // Duas "Outros" convivem porque diferem no tipo — é o que a chave
            // única (user_id, type, name) da tabela permite.
            self::row('Outros', '📦', '#78909c', CategoryType::Expense),
        ];
    }

    /**
     * @return array{name: string, icon: string, color: string, type: CategoryType}
     */
    private static function row(string $name, string $icon, string $color, CategoryType $type): array
    {
        return compact('name', 'icon', 'color', 'type');
    }
}
