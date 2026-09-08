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
        Schema::create('event_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->onDelete('cascade');
            $table->string('codigo', 50)->unique();
            $table->string('titulo', 255);
            $table->date('fecha_evento');
            $table->time('hora_evento')->nullable();
            $table->string('lugar_evento', 255)->nullable();
            $table->string('responsable_montaje', 150)->nullable();
            $table->string('responsable_desmontaje', 150)->nullable();
            $table->tinyInteger('estado')->default(0)->comment('0=Borrador/Planificado, 1=En Montaje (Salida), 2=Conforme (Completado), 3=Con Incidencias');
            $table->integer('total_items')->default(0);
            $table->integer('items_llevados')->default(0);
            $table->integer('items_devueltos')->default(0);
            $table->integer('items_con_incidencia')->default(0);
            $table->text('observaciones_salida')->nullable();
            $table->text('observaciones_retorno')->nullable();
            $table->foreignId('idusuario')->constrained('users');
            $table->foreignId('idalmacen')->nullable()->constrained('warehouses');
            $table->timestamps();
        });

        Schema::create('event_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_id')->constrained('event_checklists')->onDelete('cascade');
            $table->foreignId('contract_item_id')->nullable()->constrained('contract_items')->nullOnDelete();
            $table->foreignId('idproducto')->nullable()->constrained('products')->nullOnDelete();
            $table->string('descripcion', 255);
            $table->string('categoria', 100)->default('General');
            $table->decimal('cantidad_planeada', 10, 2)->default(1.00);
            
            // Salida / Llevado
            $table->decimal('cantidad_llevada', 10, 2)->default(0.00);
            $table->boolean('llevado')->default(false);
            $table->foreignId('idusuario_llevado')->nullable()->constrained('users');
            $table->timestamp('fecha_llevado')->nullable();

            // Retorno / Devuelto
            $table->decimal('cantidad_devuelta', 10, 2)->default(0.00);
            $table->boolean('devuelto')->default(false);
            $table->foreignId('idusuario_devuelto')->nullable()->constrained('users');
            $table->timestamp('fecha_devuelto')->nullable();

            // Incidencias (daños, roturas, pérdidas)
            $table->boolean('tiene_incidencia')->default(false);
            $table->string('tipo_incidencia', 50)->nullable()->comment('roto, extraviado, danado, incompleto');
            $table->text('observaciones')->nullable();
            $table->decimal('costo_penalidad_estimado', 10, 2)->default(0.00);
            $table->integer('orden')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_checklist_items');
        Schema::dropIfExists('event_checklists');
    }
};
