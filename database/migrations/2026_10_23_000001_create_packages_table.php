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
        if (! Schema::hasTable('packages')) {
            Schema::create('packages', function (Blueprint $table) {
                $table->id();
                $table->string('name', 150);
                $table->text('description')->nullable();
                $table->string('image', 255)->nullable();
                $table->boolean('active')->default(true);
                $table->unsignedInteger('min_guests')->default(1);
                $table->unsignedInteger('max_guests')->nullable();
                $table->string('price_mode', 30)->default('tiered'); // fixed, per_guest, tiered
                $table->decimal('base_price', 10, 2)->default(0.00);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
