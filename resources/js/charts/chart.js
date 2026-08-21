/**
 * Componente Alpine que encapsula o Chart.js. Nenhuma Blade de tela e nenhum
 * componente Livewire toca a biblioteca — só o `<x-ui.chart>` fala com este arquivo.
 */
import { Chart } from './index'
import { chartTheme, formatMoney, formatMoneyShort } from './theme'

/**
 * Cores chegam como nome de token ("income", "expense", "info") ou como cor literal
 * — o segundo caso é a cor da própria categoria, que vem do registro e não do tema.
 */
function color(value, theme) {
    return theme[value] ?? value
}

function tooltipStyle(theme, money) {
    return {
        backgroundColor: theme.surface,
        borderColor: theme.grid,
        borderWidth: 1,
        cornerRadius: 4,
        padding: 8,
        displayColors: true,
        boxWidth: 8,
        boxHeight: 8,
        titleColor: theme.foreground,
        bodyColor: theme.foreground,
        titleFont: { family: theme.mono, size: 11 },
        bodyFont: { family: theme.mono, size: 12 },
        callbacks: money
            ? {
                  label: (item) => ` ${item.dataset.label}: ${formatMoney(item.parsed.y ?? item.parsed)}`,
              }
            : {},
    }
}

function cartesianScales(theme, money) {
    return {
        x: {
            grid: { display: false },
            border: { display: false },
            ticks: {
                color: theme.axis,
                font: { family: theme.mono, size: 11 },
            },
        },
        y: {
            beginAtZero: true,
            grid: { color: theme.grid, drawTicks: false },
            border: { display: false, dash: [3, 3] },
            ticks: {
                color: theme.axis,
                font: { family: theme.mono, size: 10 },
                padding: 8,
                callback: money ? (value) => formatMoneyShort(value) : undefined,
            },
        },
    }
}

function build({ type, labels, series, money }, theme) {
    const base = {
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 250 },
        plugins: {
            legend: { display: false },
            tooltip: tooltipStyle(theme, money),
        },
    }

    if (type === 'doughnut') {
        return {
            type: 'doughnut',
            data: {
                labels,
                datasets: series.map((set) => ({
                    data: set.data,
                    backgroundColor: (set.colors ?? [set.color]).map((value) => color(value, theme)),
                    borderColor: theme.surface,
                    borderWidth: 2,
                })),
            },
            options: { ...base, cutout: '62%' },
        }
    }

    if (type === 'line') {
        return {
            type: 'line',
            data: {
                labels,
                datasets: series.map((set) => ({
                    label: set.label,
                    data: set.data,
                    borderColor: color(set.color, theme),
                    backgroundColor: `${color(set.color, theme)}22`,
                    borderWidth: 2,
                    pointRadius: 2,
                    pointHoverRadius: 4,
                    tension: 0.3,
                    fill: set.fill ?? false,
                })),
            },
            options: { ...base, scales: cartesianScales(theme, money) },
        }
    }

    return {
        type: 'bar',
        data: {
            labels,
            datasets: series.map((set) => ({
                label: set.label,
                data: set.data,
                backgroundColor: color(set.color, theme),
                borderRadius: { topLeft: 2, topRight: 2 },
                barThickness: 14,
                maxBarThickness: 14,
            })),
        },
        options: {
            ...base,
            scales: cartesianScales(theme, money),
        },
    }
}

export default function registerChart() {
    document.addEventListener('alpine:init', () => {
        window.Alpine.data('chart', (config) => {
            // A instância do Chart.js mora aqui, no fechamento, e **não** como
            // propriedade do objeto devolvido ao Alpine.
            //
            // O Alpine embrulha o que devolvemos em proxy reativo. O objeto do
            // Chart.js é um grafo grande e circular (chart → canvas → chart,
            // escala → chart), então guardá-lo numa propriedade fazia o
            // `update()` percorrer o proxy e estourar com "Maximum call stack
            // size exceeded" — a construção passava, porque acontece antes da
            // atribuição; quem quebrava era a atualização por evento. Variável
            // de fechamento nunca é proxiada, e o Alpine chama esta fábrica uma
            // vez por elemento, então cada gráfico tem a sua.
            let instance = null

            return {
                init() {
                    instance = new Chart(this.$refs.canvas, build(config, chartTheme()))

                    // O Livewire nunca re-renderiza este bloco (wire:ignore); a atualização
                    // de dados chega por evento e é repassada para o próprio Chart.js.
                    this.$el.addEventListener('chart:data', (event) => this.replace(event.detail))

                    // Mesmo caminho, vindo do servidor: `$this->dispatch('chart:data', ...)`
                    // cai na window, então o gráfico só aceita o que traz o seu próprio nome.
                    window.addEventListener('chart:data', (event) => {
                        if (! config.name || event.detail?.name !== config.name) return

                        this.replace(event.detail)
                    })
                },

                replace({ labels, series }) {
                    if (! instance) {
                        return
                    }

                    const theme = chartTheme()

                    instance.data.labels = labels
                    series.forEach((set, index) => {
                        const dataset = instance.data.datasets[index]

                        if (! dataset) return

                        dataset.data = set.data

                        // O rosca pinta fatia a fatia: trocar o período troca as categorias.
                        if (set.colors) {
                            dataset.backgroundColor = set.colors.map((value) => color(value, theme))
                        }
                    })
                    instance.update()
                },

                destroy() {
                    instance?.destroy()
                    instance = null
                },
            }
        })
    })
}
