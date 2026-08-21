<?php

declare(strict_types=1);

use App\Actions\Categories\CreateCategory;
use App\Enums\CategoryType;
use App\Models\User;

it('cria a categoria para o usuário', function (): void {
    $user = User::factory()->create();

    $category = app(CreateCategory::class)->handle(
        user: $user,
        name: 'Pet',
        icon: '🐾',
        color: '#26c6da',
        type: CategoryType::Expense,
    );

    expect($category->user_id)->toBe($user->id)
        ->and($category->name)->toBe('Pet')
        ->and($category->type)->toBe(CategoryType::Expense)
        ->and($category->exists)->toBeTrue();
});

it('normaliza o nome e a cor', function (): void {
    $category = app(CreateCategory::class)->handle(
        user: User::factory()->create(),
        name: '  Assinaturas  ',
        icon: '📱',
        // O seletor nativo de cor devolve maiúsculas.
        color: '#EF5350',
        type: CategoryType::Both,
    );

    expect($category->name)->toBe('Assinaturas')
        ->and($category->color)->toBe('#ef5350');
});
