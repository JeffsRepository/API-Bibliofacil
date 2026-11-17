<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emprestimos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('usuarios')->restrictOnDelete(); // Não pode excluir caso exista emprestimo com esse cliente. O mesmo vale para os demais.
            $table->foreignId('funcionario_id')->constrained('usuarios')->restrictOnDelete();
            $table->foreignId('exemplar_id')->constrained('exemplares')->restrictOnDelete();
            $table->date('data_emprestimo');
            $table->date('data_devolucao_prevista');
            $table->date('data_devolucao')->nullable(); // nulo = em aberto
            $table->decimal('valor_cobrado', 10, 2);
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->index(['data_devolucao', 'data_devolucao_prevista']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emprestimos');
    }
};
