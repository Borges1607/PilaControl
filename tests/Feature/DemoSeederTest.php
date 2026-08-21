<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\DemoSeeder;

it('põe o conteúdo do protótipo no banco', function (): void {
    $user = User::factory()->create();

    app(DemoSeeder::class)->run($user);

    expect($user->transactions()->count())->toBe(33)
        ->and($user->budgets()->count())->toBe(7)
        ->and($user->goals()->count())->toBe(4)
        // Três meses de lançamentos, do corrente para trás.
        ->and($user->transactions()->min('date'))
        ->toStartWith(now()->startOfMonth()->subMonths(2)->format('Y-m'));
});

it('não duplica em quem já tem lançamento', function (): void {
    $user = User::factory()->create();
    $seeder = app(DemoSeeder::class);

    $seeder->run($user);
    $seeder->run($user);

    expect($user->transactions()->count())->toBe(33);
});
