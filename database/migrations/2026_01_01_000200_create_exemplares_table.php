<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cada unidade física. O estoque = exemplares com situacao "disponivel".
        Schema::create('exemplares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('livro_id')->constrained('livros')->restrictOnDelete();
            $table->string('codigo_barras')->unique();
            $table->enum('estado', ['novo', 'bom', 'desgastado', 'danificado'])->default('bom');
            $table->enum('situacao', ['disponivel', 'emprestado', 'vendido'])->default('disponivel');
            $table->timestamps();

            $table->index(['livro_id', 'situacao']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exemplares');
    }
};
