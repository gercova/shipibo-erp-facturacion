<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Cross-reference links on contract_installments
        Schema::table('contract_installments', function (Blueprint $table) {
            if (! Schema::hasColumn('contract_installments', 'billing_id')) {
                $table->unsignedBigInteger('billing_id')->nullable()->after('estado');
                $table->foreign('billing_id')->references('id')->on('billings')->nullOnDelete();
            }
            if (! Schema::hasColumn('contract_installments', 'facturado_at')) {
                $table->timestamp('facturado_at')->nullable()->after('billing_id');
            }
            if (! Schema::hasColumn('contract_installments', 'factura_numero')) {
                $table->string('factura_numero', 30)->nullable()->after('facturado_at');
            }
        });

        // 2. Cross-reference links on billings
        Schema::table('billings', function (Blueprint $table) {
            if (! Schema::hasColumn('billings', 'contract_id')) {
                $table->unsignedBigInteger('contract_id')->nullable()->after('idcliente');
                $table->foreign('contract_id')->references('id')->on('contracts')->nullOnDelete();
            }
            if (! Schema::hasColumn('billings', 'installment_id')) {
                $table->unsignedBigInteger('installment_id')->nullable()->after('contract_id');
                $table->foreign('installment_id')->references('id')->on('contract_installments')->nullOnDelete();
            }
            if (! Schema::hasColumn('billings', 'checklist_id')) {
                $table->unsignedBigInteger('checklist_id')->nullable()->after('installment_id');
                $table->foreign('checklist_id')->references('id')->on('event_checklists')->nullOnDelete();
            }
        });

        // 3. Cross-reference links on shipment_guides
        Schema::table('shipment_guides', function (Blueprint $table) {
            if (! Schema::hasColumn('shipment_guides', 'contract_id')) {
                $table->unsignedBigInteger('contract_id')->nullable()->after('idalmacen');
                $table->foreign('contract_id')->references('id')->on('contracts')->nullOnDelete();
            }
            if (! Schema::hasColumn('shipment_guides', 'checklist_id')) {
                $table->unsignedBigInteger('checklist_id')->nullable()->after('contract_id');
                $table->foreign('checklist_id')->references('id')->on('event_checklists')->nullOnDelete();
            }
        });

        // 4. Cross-reference links on event_checklists
        Schema::table('event_checklists', function (Blueprint $table) {
            if (! Schema::hasColumn('event_checklists', 'shipment_guide_id')) {
                $table->unsignedBigInteger('shipment_guide_id')->nullable()->after('contract_id');
                $table->foreign('shipment_guide_id')->references('id')->on('shipment_guides')->nullOnDelete();
            }
        });

        // 5. Update catalog service items (opcion = 2) in products
        // Ensure SUNAT code, unit of measure ZZ (id 61), opcion = 2, and IGV tax treatment are properly set
        // WITHOUT creating duplicates.
        $this->updateExistingCatalogServices();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_checklists', function (Blueprint $table) {
            if (Schema::hasColumn('event_checklists', 'shipment_guide_id')) {
                $table->dropForeign(['shipment_guide_id']);
                $table->dropColumn('shipment_guide_id');
            }
        });

        Schema::table('shipment_guides', function (Blueprint $table) {
            if (Schema::hasColumn('shipment_guides', 'checklist_id')) {
                $table->dropForeign(['checklist_id']);
                $table->dropColumn('checklist_id');
            }
            if (Schema::hasColumn('shipment_guides', 'contract_id')) {
                $table->dropForeign(['contract_id']);
                $table->dropColumn('contract_id');
            }
        });

        Schema::table('billings', function (Blueprint $table) {
            if (Schema::hasColumn('billings', 'checklist_id')) {
                $table->dropForeign(['checklist_id']);
                $table->dropColumn('checklist_id');
            }
            if (Schema::hasColumn('billings', 'installment_id')) {
                $table->dropForeign(['installment_id']);
                $table->dropColumn('installment_id');
            }
            if (Schema::hasColumn('billings', 'contract_id')) {
                $table->dropForeign(['contract_id']);
                $table->dropColumn('contract_id');
            }
        });

        Schema::table('contract_installments', function (Blueprint $table) {
            if (Schema::hasColumn('contract_installments', 'billing_id')) {
                $table->dropForeign(['billing_id']);
                $table->dropColumn(['billing_id', 'facturado_at', 'factura_numero']);
            }
        });
    }

    /**
     * Ensure existing service products have valid SUNAT code, unit ZZ, opcion=2 and IGV treatment.
     */
    private function updateExistingCatalogServices(): void
    {
        // 1. Servicio de Mozo
        DB::table('products')
            ->where('descripcion', 'like', '%SERVICIO DE MOZO%')
            ->orWhere('id', 36)
            ->update([
                'codigo_sunat' => '90101600', // Servicios de banquetes y personal de catering
                'idunidad' => 61,         // ZZ - UNIDAD (SERVICIOS)
                'opcion' => 2,          // Servicio
                'idcodigo_igv' => 1,          // Gravado 10
                'igv' => 18,
            ]);

        // 2. Costo por transporte de alquileres / Flete
        DB::table('products')
            ->where('descripcion', 'like', '%TRANSPORTE DE ALQUILERES%')
            ->orWhere('id', 37)
            ->update([
                'codigo_sunat' => '78101802', // Servicio de transporte de carga por carretera
                'idunidad' => 61,
                'opcion' => 2,
                'idcodigo_igv' => 1,
                'igv' => 18,
            ]);

        // 3. Servicio de Delivery / Flete
        DB::table('products')
            ->where('descripcion', 'like', '%SERVICIO DE DELIVERY%')
            ->orWhere('id', 9)
            ->update([
                'codigo_sunat' => '78102203', // Servicios de entrega a domicilio / fletes
                'idunidad' => 61,
                'opcion' => 2,
                'idcodigo_igv' => 1,
                'igv' => 18,
            ]);

        // 4. Servicio de Barman / Bartender
        DB::table('products')
            ->where('descripcion', 'like', '%SERVICIO DE BARMAN%')
            ->orWhere('id', 15)
            ->update([
                'codigo_sunat' => '80141600', // Servicios de personal para eventos
                'idunidad' => 61,
                'opcion' => 2,
                'idcodigo_igv' => 1,
                'igv' => 18,
            ]);

        // 5. Alquiler de Barras Móviles
        DB::table('products')
            ->where('descripcion', 'like', '%ALQUILER BARRA%')
            ->update([
                'codigo_sunat' => '80141607', // Alquiler de implementos para banquetes y eventos
                'idunidad' => 61,
                'opcion' => 2,
                'idcodigo_igv' => 1,
                'igv' => 18,
            ]);

        // 6. Alquiler de Cristalería / Menajes
        DB::table('products')
            ->where('descripcion', 'like', '%ALQUILER VASO%')
            ->orWhere('descripcion', 'like', '%ALQUILER DE CONGELADORA%')
            ->update([
                'codigo_sunat' => '80141607',
                'idunidad' => 61,
                'opcion' => 2,
                'idcodigo_igv' => 1,
                'igv' => 18,
            ]);
    }
};
