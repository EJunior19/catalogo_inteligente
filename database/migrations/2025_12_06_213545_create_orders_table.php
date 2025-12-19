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
    Schema::create('orders', function (Blueprint $table) {
        $table->id();

        // si más adelante usás auth de clientes, acá podés relacionar
        $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

        // Datos del cliente en el momento del pedido
        $table->string('nombre_cliente');
        $table->string('email')->nullable();
        $table->string('telefono');
        $table->string('documento')->nullable();
        $table->string('direccion')->nullable();
        $table->string('ciudad')->nullable();

        $table->integer('total');
        $table->string('metodo'); // transferencia, efectivo, tarjeta, etc.

        $table->string('estado', 30)->default('pendiente'); // pendiente, enviado_erp, error_erp

        $table->boolean('enviado_a_erp')->default(false);
        $table->unsignedBigInteger('erp_pedido_id')->nullable();

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
