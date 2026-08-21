<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('icon', 8);              // emoji
            $table->bigInteger('target_cents');

            // Campo, não soma: nenhuma tela mostra histórico de aportes. Se um
            // dia mostrar, entra `goal_contributions` e isto vira soma — como
            // Goal::saved() é a única leitura, a view não muda.
            $table->bigInteger('current_cents')->default(0);

            $table->date('deadline');
            $table->timestamps();

            $table->index(['user_id', 'deadline']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goals');
    }
};
