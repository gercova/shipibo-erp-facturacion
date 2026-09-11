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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();

            // Foreign keys matching Expense model relationships
            $table->unsignedBigInteger('idarqueocaja')->nullable(); // ArchingCash
            $table->unsignedBigInteger('idcaja')->nullable();       // Cash
            $table->unsignedBigInteger('idalmacen')->nullable();    // Warehouse
            $table->unsignedBigInteger('idusuario')->nullable();    // User

            $table->date('fecha');
            $table->time('hora')->nullable();

            $table->string('tipo_comprobante', 30)->nullable();
            $table->string('nro_comprobante', 50)->nullable();
            $table->string('motivo', 255)->nullable();

            $table->decimal('monto', 12, 2)->default(0);
            $table->string('metodo_pago', 50)->nullable();
            $table->string('beneficiario', 150)->nullable();
            $table->text('observaciones')->nullable();

            // 1 = activo, 0 = anulado
            $table->tinyInteger('estado')->default(1);

            $table->timestamps();

            // Indexes for common query patterns used in DailyCashClosingController
            $table->index(['fecha', 'estado']);
            $table->index(['idarqueocaja', 'estado']);
            $table->index(['idalmacen', 'estado']);

            // Foreign key constraints
            $table->foreign('idarqueocaja')->references('id')->on('arching_cashes')->nullOnDelete();
            $table->foreign('idcaja')->references('id')->on('cashes')->nullOnDelete();
            $table->foreign('idalmacen')->references('id')->on('warehouses')->nullOnDelete();
            $table->foreign('idusuario')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
