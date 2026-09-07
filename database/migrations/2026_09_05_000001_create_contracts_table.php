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
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number', 50)->unique();
            $table->string('title', 255)->default('CONTRATO DE PRESTACIÓN DE SERVICIOS');
            $table->unsignedBigInteger('idcliente');
            $table->string('provider_name', 255)->nullable();
            $table->string('provider_document', 50)->nullable();
            $table->string('provider_representative', 255)->nullable();
            $table->date('fecha_evento');
            $table->time('hora_evento')->nullable();
            $table->string('lugar_evento', 255)->nullable();
            $table->date('fecha_emision');
            $table->date('fecha_vencimiento')->nullable();
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('igv', 18, 2)->default(0);
            $table->decimal('total', 18, 2)->default(0);
            $table->string('moneda', 10)->default('PEN');
            $table->text('observaciones')->nullable();
            $table->string('firma_cliente', 255)->nullable();
            $table->string('firma_proveedor', 255)->nullable();
            $table->tinyInteger('estado')->default(1)->comment('0: Borrador, 1: Firmado/Activo, 2: Finalizado, 3: Anulado');
            $table->unsignedBigInteger('idusuario')->nullable();
            $table->unsignedBigInteger('idalmacen')->nullable();
            $table->timestamps();

            $table->foreign('idcliente')->references('id')->on('clients')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
