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
    Schema::create('catalog_product_images', function (Blueprint $table) {
        $table->id();
        $table->foreignId('catalog_product_id')
              ->constrained('catalog_products')
              ->cascadeOnDelete();

        $table->string('url');             // url_original del scraper o local
        $table->boolean('es_principal')->default(false);

        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalog_product_images');
    }
};
