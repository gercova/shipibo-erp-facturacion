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
        if (! Schema::hasTable('package_price_tiers')) {
            Schema::create('package_price_tiers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('package_id')->constrained('packages')->cascadeOnDelete();
                $table->unsignedInteger('from_guests');
                $table->unsignedInteger('to_guests');
                $table->decimal('price', 10, 2);
                $table->string('price_type', 20)->default('total'); // total, per_guest
                $table->timestamps();

                $table->index(['package_id', 'from_guests', 'to_guests']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('package_price_tiers');
    }
};
