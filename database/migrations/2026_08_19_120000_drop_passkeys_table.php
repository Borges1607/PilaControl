<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Passkeys saíram do projeto: o login usa senha ou Google, e o cadastro de
 * chaves na tela de Segurança foi removido junto.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('passkeys');
    }

    public function down(): void
    {
        // Sem volta: a tabela e o recurso que a usava não existem mais.
    }
};
