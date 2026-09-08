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
        Schema::table('contracts', function (Blueprint $table) {
            if (!Schema::hasColumn('contracts', 'cuotas')) {
                $table->json('cuotas')->nullable()->after('total');
            }
        });

        Schema::create('contract_installments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id');
            $table->integer('numero_cuota')->default(1);
            $table->string('descripcion', 255);
            $table->decimal('monto', 18, 2)->default(0);
            $table->decimal('porcentaje', 5, 2)->nullable();
            $table->date('fecha_vencimiento');
            $table->date('fecha_pago')->nullable();
            $table->tinyInteger('estado')->default(0)->comment('0: Pendiente, 1: Pagado');
            $table->string('metodo_pago', 100)->nullable();
            $table->string('referencia_pago', 255)->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->foreign('contract_id')->references('id')->on('contracts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_installments');

        Schema::table('contracts', function (Blueprint $table) {
            if (Schema::hasColumn('contracts', 'cuotas')) {
                $table->dropColumn('cuotas');
            }
        });
    }
};
