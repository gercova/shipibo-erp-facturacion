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
        // 1. Contract Installments reconciliation & cash register fields
        Schema::table('contract_installments', function (Blueprint $table) {
            if (! Schema::hasColumn('contract_installments', 'idarqueocaja')) {
                $table->unsignedBigInteger('idarqueocaja')->nullable()->after('estado');
                $table->foreign('idarqueocaja')->references('id')->on('arching_cashes')->nullOnDelete();
            }

            if (! Schema::hasColumn('contract_installments', 'idpago')) {
                $table->integer('idpago')->nullable()->after('metodo_pago');
            }

            if (! Schema::hasColumn('contract_installments', 'reconciliation_key')) {
                $table->string('reconciliation_key', 50)->nullable()->index()->after('idpago');
            }
        });

        // 2. Detail Payments reconciliation & installment links
        Schema::table('detail_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('detail_payments', 'installment_id')) {
                $table->unsignedBigInteger('installment_id')->nullable()->after('idfactura');
                $table->foreign('installment_id')->references('id')->on('contract_installments')->nullOnDelete();
            }

            if (! Schema::hasColumn('detail_payments', 'reconciliation_key')) {
                $table->string('reconciliation_key', 50)->nullable()->index()->after('installment_id');
            }
        });

        // 3. Billings reconciliation key
        Schema::table('billings', function (Blueprint $table) {
            if (! Schema::hasColumn('billings', 'reconciliation_key')) {
                $table->string('reconciliation_key', 50)->nullable()->index()->after('installment_id');
            }
        });

        // 4. Sale Notes reconciliation key
        Schema::table('sale_notes', function (Blueprint $table) {
            if (! Schema::hasColumn('sale_notes', 'reconciliation_key')) {
                $table->string('reconciliation_key', 50)->nullable()->index()->after('correlativo');
            }
        });

        // 5. Cash Movements table for deposits and withdrawals
        if (! Schema::hasTable('cash_movements')) {
            Schema::create('cash_movements', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('idarqueocaja')->nullable();
                $table->unsignedBigInteger('idcaja')->nullable();
                $table->unsignedBigInteger('idalmacen')->nullable();
                $table->unsignedBigInteger('idusuario')->nullable();

                // 'deposit' (ingreso) or 'withdrawal' (retiro)
                $table->string('tipo', 20);
                $table->decimal('monto', 12, 2)->default(0);
                $table->string('motivo', 255)->nullable();
                $table->string('metodo_pago', 50)->default('Efectivo');
                $table->date('fecha');
                $table->time('hora')->nullable();
                $table->string('beneficiario', 150)->nullable();
                $table->text('observaciones')->nullable();
                $table->tinyInteger('estado')->default(1); // 1 = activo, 0 = anulado
                $table->timestamps();

                $table->index(['fecha', 'estado']);
                $table->index(['idarqueocaja', 'estado']);
                $table->index(['tipo', 'estado']);

                $table->foreign('idarqueocaja')->references('id')->on('arching_cashes')->nullOnDelete();
                $table->foreign('idcaja')->references('id')->on('cashes')->nullOnDelete();
                $table->foreign('idalmacen')->references('id')->on('warehouses')->nullOnDelete();
                $table->foreign('idusuario')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_movements');

        Schema::table('sale_notes', function (Blueprint $table) {
            if (Schema::hasColumn('sale_notes', 'reconciliation_key')) {
                $table->dropColumn('reconciliation_key');
            }
        });

        Schema::table('billings', function (Blueprint $table) {
            if (Schema::hasColumn('billings', 'reconciliation_key')) {
                $table->dropColumn('reconciliation_key');
            }
        });

        Schema::table('detail_payments', function (Blueprint $table) {
            if (Schema::hasColumn('detail_payments', 'installment_id')) {
                $table->dropForeign(['installment_id']);
                $table->dropColumn(['installment_id', 'reconciliation_key']);
            }
        });

        Schema::table('contract_installments', function (Blueprint $table) {
            if (Schema::hasColumn('contract_installments', 'idarqueocaja')) {
                $table->dropForeign(['idarqueocaja']);
                $table->dropColumn(['idarqueocaja', 'idpago', 'reconciliation_key']);
            }
        });
    }
};
