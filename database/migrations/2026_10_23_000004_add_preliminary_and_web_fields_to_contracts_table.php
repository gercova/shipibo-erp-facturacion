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
        Schema::table('contracts', function (Blueprint $table) {
            if (! Schema::hasColumn('contracts', 'status')) {
                $table->string('status', 30)->default('vigente')->after('estado'); // preliminar, borrador, vigente, cerrado, anulado
            }
            if (! Schema::hasColumn('contracts', 'source')) {
                $table->string('source', 20)->default('interno')->after('status'); // interno, web
            }
            if (! Schema::hasColumn('contracts', 'tracking_code')) {
                $table->string('tracking_code', 32)->nullable()->unique()->after('source');
            }
            if (! Schema::hasColumn('contracts', 'guest_count')) {
                $table->unsignedInteger('guest_count')->nullable()->after('tracking_code');
            }
            if (! Schema::hasColumn('contracts', 'event_type')) {
                $table->string('event_type', 100)->nullable()->after('guest_count');
            }
        });

        // Backfill existing rows without altering historic meaning
        DB::table('contracts')
            ->whereNull('status')
            ->orWhere('status', '')
            ->update([
                'status' => 'vigente',
                'source' => 'interno',
            ]);

        DB::table('contracts')
            ->whereNull('source')
            ->orWhere('source', '')
            ->update([
                'source' => 'interno',
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn(['status', 'source', 'tracking_code', 'guest_count', 'event_type']);
        });
    }
};
