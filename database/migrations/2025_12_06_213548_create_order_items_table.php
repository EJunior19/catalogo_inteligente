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
    Schema::create('order_items', function (Blueprint $table) {
        $table->id();

        $table->foreignId('order_id')
              ->constrained('orders')
              ->cascadeOnDelete();

        $table->foreignId('catalog_product_id')
              ->nullable()
              ->constrained('catalog_products')
              ->nullOnDelete();

        $table->unsignedBigInteger('scraper_id'); // referencia directa al scraper
        $table->string('sku');

        $table->integer('cantidad');
        $table->integer('precio_unitario');
        $table->integer('precio_total');

        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
