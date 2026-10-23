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
        Schema::table('event_checklist_items', function (Blueprint $table) {
            $table->decimal('qty_enviada', 12, 4)->default(0.0000)->after('cantidad_planeada');
            $table->decimal('qty_ok', 12, 4)->default(0.0000)->after('qty_enviada');
            $table->decimal('qty_danada', 12, 4)->default(0.0000)->after('qty_ok');
            $table->decimal('qty_quebrada', 12, 4)->default(0.0000)->after('qty_danada');
            $table->decimal('qty_faltante', 12, 4)->default(0.0000)->after('qty_quebrada');
            $table->decimal('qty_consumida', 12, 4)->default(0.0000)->after('qty_faltante');
        });

        Schema::table('event_checklists', function (Blueprint $table) {
            $table->timestamp('retorno_cerrado_at')->nullable()->after('observaciones_retorno');
            $table->foreignId('retorno_cerrado_by')->nullable()->after('retorno_cerrado_at')->constrained('users')->nullOnDelete();
            $table->timestamp('stock_aplicado_at')->nullable()->after('retorno_cerrado_by');
            $table->timestamp('reabierto_at')->nullable()->after('stock_aplicado_at');
            $table->foreignId('reabierto_by')->nullable()->after('reabierto_at')->constrained('users')->nullOnDelete();
            $table->text('motivo_reapertura')->nullable()->after('reabierto_by');
        });

        if (! Schema::hasColumn('products', 'replacement_cost')) {
            Schema::table('products', function (Blueprint $table) {
                $table->decimal('replacement_cost', 12, 4)->nullable()->after('precio_venta');
            });
        }

        if (! Schema::hasTable('inventory_movements')) {
            Schema::create('inventory_movements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
                $table->string('type', 50)->default('retorno_evento');
                $table->string('concept', 50)->nullable();
                $table->decimal('quantity', 12, 4)->default(0.0000);
                $table->smallInteger('movement_sign')->default(-1); // -1 = Salida/Deducción, +1 = Ingreso/Reversión
                $table->foreignId('checklist_id')->nullable()->constrained('event_checklists')->nullOnDelete();
                $table->foreignId('checklist_item_id')->nullable()->constrained('event_checklist_items')->nullOnDelete();
                $table->foreignId('contract_id')->nullable()->constrained('contracts')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['product_id', 'warehouse_id']);
                $table->index(['type', 'created_at']);
                $table->index('checklist_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');

        if (Schema::hasColumn('products', 'replacement_cost')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('replacement_cost');
            });
        }

        Schema::table('event_checklists', function (Blueprint $table) {
            $table->dropForeign(['retorno_cerrado_by']);
            $table->dropForeign(['reabierto_by']);
            $table->dropColumn([
                'retorno_cerrado_at',
                'retorno_cerrado_by',
                'stock_aplicado_at',
                'reabierto_at',
                'reabierto_by',
                'motivo_reapertura',
            ]);
        });

        Schema::table('event_checklist_items', function (Blueprint $table) {
            $table->dropColumn([
                'qty_enviada',
                'qty_ok',
                'qty_danada',
                'qty_quebrada',
                'qty_faltante',
                'qty_consumida',
            ]);
        });
    }
};
