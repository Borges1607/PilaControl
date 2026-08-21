<?php

namespace Database\Seeders;

use App\Actions\Categories\CreateDefaultCategories;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // O `UserObserver` faria isso sozinho, mas o `WithoutModelEvents` acima
        // cala os eventos durante o seed. Chamar a Action é o mesmo caminho.
        app(CreateDefaultCategories::class)->handle($user);
    }
}
