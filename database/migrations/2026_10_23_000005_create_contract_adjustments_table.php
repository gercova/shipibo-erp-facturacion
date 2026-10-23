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
        // 1. Add preliminary_number to contracts table to retain original PRE- code
        Schema::table('contracts', function (Blueprint $table) {
            if (! Schema::hasColumn('contracts', 'preliminary_number')) {
                $table->string('preliminary_number', 50)->nullable()->after('contract_number');
            }
        });

        // 2. Create contract_adjustments table for post-event audit trail
        Schema::create('contract_adjustments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id');
            $table->unsignedBigInteger('installment_id')->nullable();
            $table->string('type', 50)->comment('breakage, missing_item, additional_consumption, discount, manual');
            $table->text('description');
            $table->decimal('amount', 18, 2)->comment('Signed amount: positive adds to total/installment, negative discounts');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('checklist_item_id')->nullable()->comment('Linked to B4 return checklist item if applicable');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('contract_id')->references('id')->on('contracts')->onDelete('cascade');
            $table->foreign('installment_id')->references('id')->on('contract_installments')->onDelete('set null');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->index(['contract_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_adjustments');
        Schema::table('contracts', function (Blueprint $table) {
            if (Schema::hasColumn('contracts', 'preliminary_number')) {
                $table->dropColumn('preliminary_number');
            }
        });
    }
};
