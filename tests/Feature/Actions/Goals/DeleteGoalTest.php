<?php

declare(strict_types=1);

use App\Actions\Goals\DeleteGoal;
use App\Models\Goal;

it('apaga a meta', function (): void {
    $goal = Goal::factory()->create();

    app(DeleteGoal::class)->handle($goal);

    expect(Goal::query()->find($goal->id))->toBeNull();
});

it('não encosta nas outras metas da conta', function (): void {
    $goal = Goal::factory()->create();
    $outra = Goal::factory()->for($goal->user)->create();

    app(DeleteGoal::class)->handle($goal);

    expect($goal->user->goals()->pluck('id')->all())->toBe([$outra->id]);
});
