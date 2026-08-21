<?php

declare(strict_types=1);

use App\Actions\Categories\CreateDefaultCategories;
use App\Models\User;
use App\Support\DefaultCategories;

it('dá o conjunto padrão a toda conta nova', function (): void {
    $user = User::factory()->create();

    expect($user->categories()->count())->toBe(count(DefaultCategories::all()))
        // A ordem de cadastro é a ordem de listagem: receitas primeiro.
        ->and($user->categories()->orderBy('id')->value('name'))->toBe('Salário');
});

it('não repete o conjunto em quem já tem categoria', function (): void {
    $user = User::factory()->create();

    app(CreateDefaultCategories::class)->handle($user);

    expect($user->categories()->count())->toBe(count(DefaultCategories::all()));
});

it('não mistura o registro de dois usuários', function (): void {
    $um = User::factory()->create();
    $outro = User::factory()->create();

    expect($um->categories()->pluck('id')->intersect($outro->categories()->pluck('id')))
        ->toBeEmpty();
});
