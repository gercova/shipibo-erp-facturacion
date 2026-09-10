<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detail_sale_notes', function (Blueprint $table) {
            // Make idproducto nullable to support custom (non-inventory) items
            $table->integer('idproducto')->nullable()->change();
            // Store free-text description for custom items
            $table->string('descripcion_custom')->nullable()->after('idproducto');
            // Store unit label for custom items (e.g. "UND", "PIEZA")
            $table->string('unidad_custom')->nullable()->after('descripcion_custom');
        });
    }

    public function down(): void
    {
        Schema::table('detail_sale_notes', function (Blueprint $table) {
            $table->dropColumn(['descripcion_custom', 'unidad_custom']);
            $table->integer('idproducto')->nullable(false)->change();
        });
    }
};
