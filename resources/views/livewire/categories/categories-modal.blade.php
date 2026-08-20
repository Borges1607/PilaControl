@php
    $tabs = ['all' => 'Todas', 'income' => 'Receitas', 'expense' => 'Despesas'];
@endphp

<div>
    <flux:modal name="categorias" class="max-w-lg! border-border! bg-card! p-0!">
        <div class="flex max-h-[90vh] flex-col">
            <div class="border-b border-border px-5 py-4">
                <flux:heading size="lg">Categorias</flux:heading>
            </div>

            {{-- Nova categoria --}}
            <form wire:submit="save" class="flex flex-col gap-3 border-b border-border p-5">
                <p class="text-xs font-semibold tracking-widest text-muted-foreground uppercase">
                    Nova Categoria
                </p>

                <div class="grid grid-cols-[72px_1fr] gap-3">
                    <flux:input label="Ícone" wire:model.live="formIcon" class="text-center! text-lg!" />

                    <flux:input label="Nome" placeholder="Ex: Pet, Assinaturas…" wire:model="formName" />
                </div>

                {{-- Atalhos de ícone. O campo acima continua aceitando qualquer emoji. --}}
                <flux:field>
                    <flux:label>Sugestões</flux:label>

                    <div class="grid max-h-[92px] grid-cols-12 gap-1 overflow-y-auto pe-1">
                        @foreach (\App\Support\CategoryPresets::icons() as $icon)
                            <button
                                type="button"
                                wire:click="$set('formIcon', '{{ $icon }}')"
                                @class([
                                    'flex size-7 items-center justify-center rounded text-base transition-colors',
                                    'bg-secondary ring-1 ring-info' => $this->formIcon === $icon,
                                    'hover:bg-white/5' => $this->formIcon !== $icon,
                                ])
                            >
                                <span aria-hidden="true">{{ $icon }}</span>
                                <span class="sr-only">Usar o ícone {{ $icon }}</span>
                            </button>
                        @endforeach
                    </div>
                </flux:field>

                {{-- Tipo --}}
                <flux:field>
                    <flux:label>Tipo</flux:label>

                    <div class="flex overflow-hidden rounded border border-border">
                        {{-- Ordem do protótipo: despesa primeiro, que é o caso comum. --}}
                        @foreach ([\App\Enums\CategoryType::Expense, \App\Enums\CategoryType::Income, \App\Enums\CategoryType::Both] as $case)
                            <button
                                type="button"
                                wire:click="$set('formType', '{{ $case->value }}')"
                                @class([
                                    'flex-1 py-1.5 text-xs font-medium transition-colors',
                                    'bg-secondary '.$case->colorClass() => $this->formType === $case->value,
                                    'text-muted-foreground hover:text-foreground' => $this->formType !== $case->value,
                                ])
                            >
                                {{ $case->label() }}
                            </button>
                        @endforeach
                    </div>
                </flux:field>

                {{-- Cor: amostras da paleta mais um seletor livre. Não é um controle
                     que o Flux cubra — a cor é dado do registro, não do tema. --}}
                <flux:field>
                    <flux:label>Cor</flux:label>

                    <div class="flex flex-wrap items-center gap-2">
                        @foreach (\App\Support\CategoryPresets::colors() as $color)
                            <button
                                type="button"
                                wire:click="$set('formColor', '{{ $color }}')"
                                title="{{ $color }}"
                                class="size-6 rounded-full transition-transform hover:scale-110"
                                style="
                                    background-color: {{ $color }};
                                    {{ $this->formColor === $color ? 'outline: 2px solid '.$color.'; outline-offset: 2px;' : '' }}
                                "
                            >
                                <span class="sr-only">Usar a cor {{ $color }}</span>
                            </button>
                        @endforeach

                        <input
                            type="color"
                            wire:model.live="formColor"
                            title="Cor personalizada"
                            class="size-6 cursor-pointer rounded-full border-0 bg-transparent p-0"
                        />
                    </div>
                </flux:field>

                @error('formName')
                    <p class="text-xs text-expense">{{ $message }}</p>
                @enderror

                @error('formColor')
                    <p class="text-xs text-expense">{{ $message }}</p>
                @enderror

                <flux:button type="submit" variant="primary" size="sm" icon="plus" class="font-semibold!">
                    Criar Categoria
                </flux:button>
            </form>

            {{-- Listagem --}}
            <div class="flex min-h-0 flex-1 flex-col">
                <div class="flex items-center gap-0.5 px-5 pt-4 pb-2">
                    @foreach ($tabs as $value => $label)
                        <button
                            type="button"
                            wire:click="setTab('{{ $value }}')"
                            @class([
                                'rounded px-3 py-1 text-xs font-medium transition-colors',
                                'bg-secondary text-foreground' => $this->tab === $value,
                                'text-muted-foreground hover:text-foreground' => $this->tab !== $value,
                            ])
                        >
                            {{ $label }}
                        </button>
                    @endforeach

                    <span class="ms-auto self-center font-mono text-xs text-muted-foreground">
                        {{ $this->visible->count() }}
                    </span>
                </div>

                <div class="flex flex-col gap-1 overflow-y-auto px-5 pb-5">
                    @foreach ($this->visible as $category)
                        <div
                            wire:key="cat-{{ $category->id }}"
                            class="group flex items-center gap-3 rounded border border-border px-3 py-2.5 transition-colors hover:bg-white/[0.03]"
                        >
                            <span
                                class="size-2.5 shrink-0 rounded-full"
                                style="background-color: {{ $category->color }}"
                                aria-hidden="true"
                            ></span>

                            <span class="w-6 shrink-0 text-center text-base" aria-hidden="true">{{ $category->icon }}</span>

                            <span class="min-w-0 flex-1 truncate text-sm">{{ $category->name }}</span>

                            <span class="shrink-0 rounded-sm px-1.5 py-0.5 text-[10px] {{ $category->type->tintClass() }} {{ $category->type->colorClass() }}">
                                {{ $category->type->label() }}
                            </span>

                            @if ($this->isDefault($category->id))
                                <span class="w-14 shrink-0 text-end text-[10px] text-muted-foreground opacity-0 transition-opacity group-hover:opacity-100">
                                    padrão
                                </span>
                            @else
                                <div class="flex w-14 shrink-0 justify-end">
                                    <flux:button
                                        size="xs"
                                        variant="subtle"
                                        icon="x-mark"
                                        wire:click="delete('{{ $category->id }}')"
                                        wire:confirm="Remover a categoria {{ $category->name }}?"
                                        class="opacity-0 transition-opacity group-hover:opacity-100 focus-visible:opacity-100 text-expense!"
                                    >
                                        <span class="sr-only">Remover {{ $category->name }}</span>
                                    </flux:button>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </flux:modal>
</div>
