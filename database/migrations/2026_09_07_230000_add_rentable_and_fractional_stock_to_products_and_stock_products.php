<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add 'rentable' column to products table if not exists
        if (!Schema::hasColumn('products', 'rentable')) {
            Schema::table('products', function (Blueprint $table) {
                $table->boolean('rentable')->default(false)->after('opcion')->index();
            });
        }

        // 2. Alter products.stock_actual to decimal(12, 4) to support fractional stock (e.g. 0.5 bottles)
        DB::statement("ALTER TABLE `products` MODIFY `stock_actual` DECIMAL(12, 4) NULL DEFAULT 0.0000");

        // 3. Alter stock_products columns to decimal(12, 4) for warehouse stock precision
        DB::statement("ALTER TABLE `stock_products` MODIFY `stock_actual` DECIMAL(12, 4) NULL DEFAULT 0.0000");
        DB::statement("ALTER TABLE `stock_products` MODIFY `stock_minimo` DECIMAL(12, 4) NULL DEFAULT 0.0000");
        DB::statement("ALTER TABLE `stock_products` MODIFY `stock_entrada` DECIMAL(12, 4) NULL DEFAULT 0.0000");

        // 4. Ensure default Category "Herramientas de Barra / Alquiler" exists
        $exists = DB::table('categories')->where('descripcion', 'LIKE', '%HERRAMIENTAS DE BARRA%')->exists();
        if (!$exists) {
            DB::table('categories')->insert([
                'descripcion' => 'HERRAMIENTAS DE BARRA / ALQUILER',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('products', 'rentable')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('rentable');
            });
        }

        DB::statement("ALTER TABLE `products` MODIFY `stock_actual` INT(11) NULL DEFAULT 0");
        DB::statement("ALTER TABLE `stock_products` MODIFY `stock_actual` INT(11) NULL DEFAULT 0");
        DB::statement("ALTER TABLE `stock_products` MODIFY `stock_minimo` INT(11) NULL DEFAULT 0");
        DB::statement("ALTER TABLE `stock_products` MODIFY `stock_entrada` INT(11) NULL DEFAULT 0");
    }
};
