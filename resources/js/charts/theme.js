/**
 * Paleta dos gráficos lida dos design tokens do `app.css`. Nenhuma cor fixa aqui:
 * é isso que garante que gráfico e interface nunca saiam de sincronia.
 */
function token(name, fallback) {
    const value = getComputedStyle(document.documentElement).getPropertyValue(name).trim()

    return value || fallback
}

export function chartTheme() {
    return {
        income: token('--color-income', '#00e676'),
        expense: token('--color-expense', '#f85149'),
        info: token('--color-info', '#388bfd'),
        grid: token('--color-border', '#21262d'),
        axis: token('--color-muted-foreground', '#6e7681'),
        surface: token('--color-secondary', '#1c2128'),
        foreground: token('--color-foreground', '#e6edf3'),
        mono: "'JetBrains Mono', ui-monospace, monospace",
    }
}

const currency = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
})

export function formatMoney(value) {
    return currency.format(value ?? 0)
}

/** Forma compacta usada nos eixos: R$ 1,2k */
export function formatMoneyShort(value) {
    const amount = Math.abs(value ?? 0)
    const sign = value < 0 ? '−' : ''

    if (amount >= 1000) {
        return `${sign}R$ ${(amount / 1000).toFixed(1).replace('.', ',')}k`
    }

    return `${sign}R$ ${amount.toFixed(0)}`
}
