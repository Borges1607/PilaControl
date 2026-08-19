/**
 * Registro dos tipos de gráfico realmente usados — mantém o tree-shaking do
 * Chart.js efetivo. Ao adicionar um tipo novo, registre aqui e só aqui.
 */
import {
    ArcElement,
    BarController,
    BarElement,
    CategoryScale,
    Chart,
    DoughnutController,
    Filler,
    Legend,
    LineController,
    LineElement,
    LinearScale,
    PointElement,
    Tooltip,
} from 'chart.js'

Chart.register(
    BarController,
    BarElement,
    LineController,
    LineElement,
    PointElement,
    DoughnutController,
    ArcElement,
    CategoryScale,
    LinearScale,
    Filler,
    Tooltip,
    Legend,
)

export { Chart }
