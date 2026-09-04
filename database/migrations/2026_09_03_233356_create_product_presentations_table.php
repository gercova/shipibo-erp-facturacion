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
        Schema::create('product_presentations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('idproducto');
            $table->unsignedBigInteger('idunidad');
            $table->string('descripcion', 150);
            $table->decimal('factor_conversion', 10, 4)->default(1)->comment('Cuántas unidades base equivale esta presentación');
            $table->decimal('precio_compra', 10, 2)->default(0);
            $table->decimal('precio_venta', 10, 2)->default(0);
            $table->boolean('estado')->default(true);
            $table->timestamps();

            $table->foreign('idproducto')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('idunidad')->references('id')->on('units')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_presentations');
    }
};
