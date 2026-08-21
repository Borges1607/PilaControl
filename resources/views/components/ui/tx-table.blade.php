@props([
    'transactions',
    'deleteAction' => null,
    'scroll' => false,
    'empty' => 'Nenhuma transação encontrada',
])

@php
    $columnClasses = 'py-2! text-[10px]! font-semibold! tracking-widest! text-muted-foreground! uppercase border-border!';
@endphp

<flux:table
    :container:class="$scroll ? '[&_ui-table-scroll-area]:max-h-[520px]' : ''"
    class="px-4!"
>
    <flux:table.columns :sticky="(bool) $scroll" class="bg-card">
        <flux:table.column class="{{ $columnClasses }} w-[88px]">Data</flux:table.column>
        <flux:table.column class="{{ $columnClasses }}">Descrição</flux:table.column>
        <flux:table.column class="{{ $columnClasses }} w-[132px]">Categoria</flux:table.column>
        <flux:table.column align="end" class="{{ $columnClasses }} w-[128px]">Valor</flux:table.column>
        <flux:table.column class="{{ $columnClasses }} w-10"><span class="sr-only">Ações</span></flux:table.column>
    </flux:table.columns>

    <flux:table.rows>
        @forelse ($transactions as $tx)
            <flux:table.row :key="$tx->id" class="group transition-colors hover:bg-white/[0.02]">
                <flux:table.cell class="py-3! font-mono text-xs! text-muted-foreground!">
                    {{ \App\Support\MonthLabel::date($tx->date) }}
                </flux:table.cell>

                <flux:table.cell class="py-3! text-foreground!">
                    <div class="flex min-w-0 items-center gap-2">
                        <span class="shrink-0 text-sm" aria-hidden="true">{{ $tx->category->icon }}</span>
                        <span class="truncate text-sm">{{ $tx->description }}</span>
                    </div>
                </flux:table.cell>

                <flux:table.cell class="py-3!">
                    <x-ui.category-pill :category="$tx->category" />
                </flux:table.cell>

                <flux:table.cell align="end" class="py-3!">
                    <span class="font-mono text-sm font-medium {{ $tx->type->colorClass() }}">
                        {{ $tx->type->sign() }}{{ \App\Support\Money::fromCents($tx->amount_cents)->format() }}
                    </span>
                </flux:table.cell>

                <flux:table.cell class="py-3!">
                    @if ($deleteAction)
                        <flux:button
                            size="xs"
                            variant="subtle"
                            icon="x-mark"
                            wire:click="{{ $deleteAction }}({{ $tx->id }})"
                            wire:confirm="Remover “{{ $tx->description }}”?"
                            class="opacity-0 transition-opacity group-hover:opacity-100 focus-visible:opacity-100 text-muted-foreground!"
                        >
                            <span class="sr-only">Remover {{ $tx->description }}</span>
                        </flux:button>
                    @endif
                </flux:table.cell>
            </flux:table.row>
        @empty
            <flux:table.row>
                <flux:table.cell colspan="5" class="py-8! text-center text-sm text-muted-foreground!">
                    {{ $empty }}
                </flux:table.cell>
            </flux:table.row>
        @endforelse
    </flux:table.rows>
</flux:table>
