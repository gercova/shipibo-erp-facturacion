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
        // 1. Tabla de Categorías del Menú de Cócteles
        if (!Schema::hasTable('cocktail_menu_categories')) {
            Schema::create('cocktail_menu_categories', function (Blueprint $table) {
                $table->id();
                $table->string('nombre', 100);
                $table->string('descripcion', 255)->nullable();
                $table->string('icono', 50)->nullable()->default('ri-goblet-line');
                $table->integer('orden')->default(0);
                $table->boolean('activo')->default(true);
                $table->timestamps();
            });
        }

        // 2. Tabla de Ítems / Cócteles del Menú
        if (!Schema::hasTable('cocktail_menu_items')) {
            Schema::create('cocktail_menu_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
                $table->foreignId('menu_category_id')->constrained('cocktail_menu_categories')->onDelete('cascade');
                $table->string('nombre', 150);
                $table->text('descripcion_corta')->nullable();
                $table->string('cristaleria', 100)->nullable();
                $table->string('garnish', 255)->nullable();
                $table->string('imagen', 255)->nullable();
                $table->decimal('precio', 10, 2)->nullable();
                $table->boolean('es_autor')->default(false);
                $table->boolean('destacado')->default(false);
                $table->integer('orden')->default(0);
                $table->boolean('activo')->default(true);
                $table->timestamps();
            });
        }

        // 3. Crear Servicio de Degustación Facturable (opcion = 2) si no existe
        $existingTasting = DB::table('products')->where('codigo_interno', 'SRV-DEGUSTACION')->first();
        if (!$existingTasting) {
            $unitService = DB::table('units')->where('codigo', 'ZZ')->first()
                ?? DB::table('units')->first();
            $unitId = $unitService ? $unitService->id : 61;

            $catService = DB::table('categories')->where('descripcion', 'SERVICIOS DE COCTELERIA')->first()
                ?? DB::table('categories')->where('descripcion', 'SERVICIOS')->first()
                ?? DB::table('categories')->first();
            $catServiceId = $catService ? $catService->id : 8;

            // Buscar el tipo de afectación IGV "GRAVADO" (código SUNAT 10), con fallback al primero disponible
            $igvType = DB::table('igv_type_affections')->where('codigo', '10')->first()
                ?? DB::table('igv_type_affections')->first();

            if (!$igvType) {
                throw new \RuntimeException('No se encontró ningún registro en igv_type_affections. Asegúrate de correr el seeder correspondiente antes de esta migración.');
            }

            DB::table('products')->insert([
                'codigo_interno'    => 'SRV-DEGUSTACION',
                'codigo_barras'     => null,
                'codigo_sunat'      => '80141607',
                'descripcion'       => 'SERVICIO DE DEGUSTACIÓN PREVIA DE COCTELERÍA (10 OPCIONES / ELIGE 5)',
                'idunidad'          => $unitId,
                'idcategoria'       => $catServiceId,
                'igv'               => 18.00,
                'idcodigo_igv'      => $igvType->id,
                'precio_compra'     => 0.00,
                'precio_venta'      => 180.00,
                'opcion'            => 2,
                'rentable'          => false,
                'stock_actual'      => null,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cocktail_menu_items');
        Schema::dropIfExists('cocktail_menu_categories');
        DB::table('products')->where('codigo_interno', 'SRV-DEGUSTACION')->delete();
    }
};
