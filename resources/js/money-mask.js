
const MAX_DIGITS = 11

export function maskMoney(value) {
    const digits = String(value ?? '')
        .replace(/\D/g, '')
        .replace(/^0+(?=\d)/, '')
        .slice(-MAX_DIGITS)

    if (digits === '') {
        return ''
    }

    const padded = digits.padStart(3, '0')
    const reais = padded.slice(0, -2).replace(/\B(?=(\d{3})+(?!\d))/g, '.')

    return `${reais},${padded.slice(-2)}`
}

export default function registerMoneyMask() {
    document.addEventListener('alpine:init', () => {
        window.Alpine.directive('money-mask', (el) => {
            el.addEventListener('input', () => {
                el.value = maskMoney(el.value)
            })
        })
    })
}
