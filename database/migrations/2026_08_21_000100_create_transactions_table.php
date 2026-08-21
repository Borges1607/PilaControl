<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // restrict: categoria com lançamento não se apaga — o histórico
            // não pode perder a gaveta. DeleteCategory checa antes e avisa.
            $table->foreignId('category_id')->constrained()->restrictOnDelete();

            $table->date('date');
            $table->string('description');

            // Dinheiro é inteiro em centavos. bigInteger porque o teto do
            // formulário (R$ 99.999.999) passa de 9,9 bilhões de centavos,
            // acima do integer de 32 bits.
            $table->bigInteger('amount_cents');     // sempre positivo; o sinal vem do type

            $table->enum('type', ['income', 'expense']);
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'date']);
            $table->index(['user_id', 'type']);
            $table->index(['user_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
