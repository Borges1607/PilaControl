<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budgets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();

            // Chave "Y-m" — a mesma que Support\MonthLabel::key() devolve.
            $table->string('month', 7);

            $table->bigInteger('limit_cents');
            $table->timestamps();

            // Um limite por categoria por mês: é sobre esta chave que o
            // SetCategoryBudget faz upsert.
            $table->unique(['user_id', 'category_id', 'month']);
            $table->index(['user_id', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
