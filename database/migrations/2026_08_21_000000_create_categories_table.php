<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 40);
            $table->string('icon', 8);              // emoji
            $table->string('color', 7);             // hex "#rrggbb", minúsculo
            $table->enum('type', ['income', 'expense', 'both']);
            $table->timestamps();

            $table->index(['user_id', 'type']);

            // Duas categorias "Outros" convivem porque diferem no tipo —
            // uma é receita, a outra despesa.
            $table->unique(['user_id', 'type', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
