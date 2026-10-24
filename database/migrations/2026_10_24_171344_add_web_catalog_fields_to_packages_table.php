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
        Schema::table('packages', function (Blueprint $table) {
            if (! Schema::hasColumn('packages', 'order_num')) {
                $table->string('order_num', 20)->nullable()->after('name');
            }
            if (! Schema::hasColumn('packages', 'badge_glasses')) {
                $table->string('badge_glasses', 60)->nullable()->after('order_num');
            }
            if (! Schema::hasColumn('packages', 'tagline')) {
                $table->string('tagline', 255)->nullable()->after('badge_glasses');
            }
            if (! Schema::hasColumn('packages', 'color')) {
                $table->string('color', 30)->nullable()->default('#275c34')->after('description');
            }
            if (! Schema::hasColumn('packages', 'light_bg')) {
                $table->string('light_bg', 30)->nullable()->default('#e9edea')->after('color');
            }
            if (! Schema::hasColumn('packages', 'ideal_capacity')) {
                $table->string('ideal_capacity', 100)->nullable()->after('max_guests');
            }
            if (! Schema::hasColumn('packages', 'default_guests')) {
                $table->unsignedInteger('default_guests')->nullable()->after('ideal_capacity');
            }
            if (! Schema::hasColumn('packages', 'features')) {
                $table->json('features')->nullable()->after('default_guests');
            }
            if (! Schema::hasColumn('packages', 'is_barman')) {
                $table->boolean('is_barman')->default(false)->after('features');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $columnsToDrop = array_filter([
                'order_num',
                'badge_glasses',
                'tagline',
                'color',
                'light_bg',
                'ideal_capacity',
                'default_guests',
                'features',
                'is_barman',
            ], fn ($col) => Schema::hasColumn('packages', $col));

            if (! empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
