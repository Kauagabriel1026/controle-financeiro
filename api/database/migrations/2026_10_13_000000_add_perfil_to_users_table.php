<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Perfil financeiro, opcional: base para dicas personalizadas.
            $table->decimal('renda_mensal', 12, 2)->nullable();
            $table->unsignedTinyInteger('dia_pagamento')->nullable();
            $table->string('objetivo', 30)->nullable();
            $table->decimal('meta_economia', 12, 2)->nullable();

            // Aparência escolhida pela pessoa: claro, escuro ou a do sistema.
            $table->string('tema', 10)->default('sistema');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['renda_mensal', 'dia_pagamento', 'objetivo', 'meta_economia', 'tema']);
        });
    }
};
