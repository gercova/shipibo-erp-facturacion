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
        // 1. Indexes and Consent audit fields on contracts table
        Schema::table('contracts', function (Blueprint $table) {
            if (! Schema::hasColumn('contracts', 'consent_version')) {
                $table->string('consent_version', 20)->nullable()->default('v1.0')->after('event_type');
            }
            if (! Schema::hasColumn('contracts', 'consent_accepted_at')) {
                $table->timestamp('consent_accepted_at')->nullable()->after('consent_version');
            }
        });

        $contractIndexes = collect(DB::select('SHOW INDEX FROM contracts'))->pluck('Key_name')->all();

        Schema::table('contracts', function (Blueprint $table) use ($contractIndexes) {
            if (! in_array('contracts_status_index', $contractIndexes, true)) {
                $table->index('status', 'contracts_status_index');
            }
            if (! in_array('contracts_source_index', $contractIndexes, true)) {
                $table->index('source', 'contracts_source_index');
            }
            if (! in_array('contracts_status_source_index', $contractIndexes, true)) {
                $table->index(['status', 'source'], 'contracts_status_source_index');
            }
            if (! in_array('contracts_source_created_at_index', $contractIndexes, true)) {
                $table->index(['source', 'created_at'], 'contracts_source_created_at_index');
            }
        });

        // 2. Index on clients.telefono for fast deduplication lookup
        $clientIndexes = collect(DB::select('SHOW INDEX FROM clients'))->pluck('Key_name')->all();
        if (! in_array('clients_telefono_index', $clientIndexes, true)) {
            Schema::table('clients', function (Blueprint $table) {
                $table->index('telefono', 'clients_telefono_index');
            });
        }

        // 3. Package Price Tiers index verification
        $tierIndexes = collect(DB::select('SHOW INDEX FROM package_price_tiers'))->pluck('Key_name')->all();
        if (! in_array('package_price_tiers_guests_range_index', $tierIndexes, true)) {
            Schema::table('package_price_tiers', function (Blueprint $table) {
                $table->index(['from_guests', 'to_guests'], 'package_price_tiers_guests_range_index');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex('clients_telefono_index');
        });

        Schema::table('package_price_tiers', function (Blueprint $table) {
            $table->dropIndex('package_price_tiers_guests_range_index');
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->dropIndex('contracts_status_index');
            $table->dropIndex('contracts_source_index');
            $table->dropIndex('contracts_status_source_index');
            $table->dropIndex('contracts_source_created_at_index');
            $table->dropColumn(['consent_version', 'consent_accepted_at']);
        });
    }
};
