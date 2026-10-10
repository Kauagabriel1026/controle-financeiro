<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('categoria_id')->nullable()->constrained('categorias')->nullOnDelete();
            $table->enum('tipo', ['pagar', 'receber']);
            $table->string('descricao', 120);
            $table->decimal('valor', 12, 2);
            $table->date('vencimento');
            $table->date('pago_em')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'vencimento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contas');
    }
};
