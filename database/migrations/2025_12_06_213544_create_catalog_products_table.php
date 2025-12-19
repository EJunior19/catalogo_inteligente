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
        Schema::create('catalog_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('scraper_id')->index(); // id del producto en el scraper
            $table->string('sku')->index();
            $table->string('slug')->unique();

            $table->string('nombre_basico');      // nombre del scraper (bonito pero básico)
            $table->string('categoria')->nullable();

            $table->integer('precio_base');       // precio crudo que viene del scraper
            $table->integer('precio_contado');    // precio contado (igual al base o ajustado)
            $table->integer('precio_recargo');    // contado + X%
            $table->integer('precio_cuota_3');    // precio_recargo / 3 (redondeado)

            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['scraper_id']);       // opcional, uno a uno con el scraper
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalog_products');
    }
};
