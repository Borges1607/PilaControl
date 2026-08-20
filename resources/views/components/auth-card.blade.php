{{-- Cartão das telas de autenticação: mesma superfície dos painéis do app. --}}
<div {{ $attributes->class(['flex flex-col gap-4 rounded-xl border border-border bg-card p-6']) }}>
    {{ $slot }}
</div>
