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
        if (! Schema::hasTable('package_items')) {
            Schema::create('package_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('package_id')->constrained('packages')->cascadeOnDelete();
                $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
                $table->foreignId('cocktail_id')->nullable()->constrained('cocktail_menu_items')->nullOnDelete();
                $table->decimal('quantity', 10, 2)->default(1.00);
                $table->boolean('is_per_guest')->default(false);
                $table->boolean('is_mandatory')->default(true);
                $table->decimal('unit_price', 10, 2)->nullable();
                $table->string('notes', 255)->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('package_items');
    }
};
