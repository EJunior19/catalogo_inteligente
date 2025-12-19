<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('catalog_presentations', function (Blueprint $table) {
        $table->id();
        $table->foreignId('catalog_product_id')
              ->constrained('catalog_products')
              ->cascadeOnDelete();

        $table->string('nombre_premium')->nullable();
        $table->string('titulo_marketing');
        $table->text('resumen_corto')->nullable();

        // Guardamos bullets como JSON (array de strings)
        $table->json('bullets_sensoriales')->nullable();

        $table->string('texto_cuotas')->nullable();
        $table->string('notas_aroma')->nullable();
        $table->string('genero', 20)->nullable(); // femenino, masculino, unisex
        $table->text('historia')->nullable();

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalog_presentations');
    }
};
