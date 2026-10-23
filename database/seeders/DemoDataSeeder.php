<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Client;
use App\Models\Contract;
use App\Models\ContractInstallment;
use App\Models\ContractItem;
use App\Models\EventChecklist;
use App\Models\EventChecklistItem;
use App\Models\IdentityDocumentType;
use App\Models\Package;
use App\Models\PackageItem;
use App\Models\PackagePriceTier;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

/**
 * DemoDataSeeder
 *
 * Provides isolated demonstration data for Blocks B1-B6:
 * - 2 Event Packages with price tiers and items
 * - 1 Preliminary Web Order with tracking code, versioned consent, and items
 * - 1 Formal Contract with payment installments (including guarantee) and linked Event Checklist
 *
 * NOTE: This seeder is strictly separated from production seeders (DatabaseSeeder).
 * Run manually via: php artisan db:seed --class=DemoDataSeeder
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $business = Business::first();
        $user = User::first() ?? User::factory()->create();
        $warehouse = Warehouse::first() ?? Warehouse::create([
            'descripcion' => 'ALMACÉN PRINCIPAL DEMO',
            'direccion' => 'Jr. San Martín 123, Tarapoto',
            'estado' => 1,
        ]);
        $docTypeDni = IdentityDocumentType::where('codigo', '1')->first()
            ?? IdentityDocumentType::first();
        $docTypeRuc = IdentityDocumentType::where('codigo', '6')->first()
            ?? IdentityDocumentType::first();
        $docTypeSinDoc = IdentityDocumentType::where('codigo', '0')->first()
            ?? IdentityDocumentType::first();

        // -------------------------------------------------------------
        // 1. Two Event Packages with Tiers and Items (B1)
        // -------------------------------------------------------------
        $package1 = Package::updateOrCreate(
            ['name' => 'Pack Barra Libre Amazonía (Demo)'],
            [
                'description' => 'Servicio completo de coctelería amazónica con insumos regionales (camu camu, maracuyá, copoazú) y barra móvil rústica.',
                'min_guests' => 30,
                'max_guests' => 150,
                'price_mode' => 'tiered',
                'base_price' => 1950.00,
                'active' => true,
            ]
        );

        PackagePriceTier::updateOrCreate(
            ['package_id' => $package1->id, 'from_guests' => 30, 'to_guests' => 60],
            ['price' => 1950.00, 'price_type' => 'total']
        );
        PackagePriceTier::updateOrCreate(
            ['package_id' => $package1->id, 'from_guests' => 61, 'to_guests' => 120],
            ['price' => 3600.00, 'price_type' => 'total']
        );

        $srvBartender = Product::where('codigo_interno', 'SRV-BARTENDER')->first();
        $srvBarra = Product::where('codigo_interno', 'ALQ-BARRA-MOVIL')->first();

        PackageItem::updateOrCreate(
            ['package_id' => $package1->id, 'notes' => '1 Bartender profesional + 1 Asistente'],
            [
                'product_id' => $srvBartender?->id,
                'quantity' => 1.00,
                'is_per_guest' => false,
                'is_mandatory' => true,
                'unit_price' => 600.00,
            ]
        );
        PackageItem::updateOrCreate(
            ['package_id' => $package1->id, 'notes' => 'Barra móvil de madera rústica Shipibo'],
            [
                'product_id' => $srvBarra?->id,
                'quantity' => 1.00,
                'is_per_guest' => false,
                'is_mandatory' => true,
                'unit_price' => 450.00,
            ]
        );

        $package2 = Package::updateOrCreate(
            ['name' => 'Pack Coctelería de Autor Deluxe (Demo)'],
            [
                'description' => 'Experiencia gourmet con cócteles de autor ahumados, cristalería de cristal templado y garnish deshidratado importado.',
                'min_guests' => 20,
                'max_guests' => 200,
                'price_mode' => 'tiered',
                'base_price' => 2400.00,
                'active' => true,
            ]
        );

        PackagePriceTier::updateOrCreate(
            ['package_id' => $package2->id, 'from_guests' => 20, 'to_guests' => 50],
            ['price' => 2400.00, 'price_type' => 'total']
        );
        PackagePriceTier::updateOrCreate(
            ['package_id' => $package2->id, 'from_guests' => 51, 'to_guests' => 100],
            ['price' => 4500.00, 'price_type' => 'total']
        );

        PackageItem::updateOrCreate(
            ['package_id' => $package2->id, 'notes' => 'Mixólogo principal y técnicas moleculares'],
            [
                'product_id' => $srvBartender?->id,
                'quantity' => 1.00,
                'is_per_guest' => false,
                'is_mandatory' => true,
                'unit_price' => 850.00,
            ]
        );

        // -------------------------------------------------------------
        // 2. One Preliminary Order (Web Flow B2-B3)
        // -------------------------------------------------------------
        $clientWeb = Client::firstOrCreate(
            ['telefono' => '942000111'],
            [
                'iddoc' => $docTypeSinDoc?->id ?? 1,
                'nro_documento' => 'SD-942000111',
                'nombres' => 'VALERIA RIOS DEMO',
                'direccion' => 'Tarapoto, San Martín',
            ]
        );

        $preliminaryOrder = Contract::updateOrCreate(
            ['tracking_code' => 'SHP-DEMO01'],
            [
                'contract_number' => 'PRE-DEMO-001',
                'title' => 'PEDIDO WEB PRELIMINAR - '.$package1->name,
                'idcliente' => $clientWeb->id,
                'provider_name' => $business?->razon_social ?? 'MYTEMS E.I.R.L.',
                'provider_document' => $business?->ruc ?? '20600000000',
                'provider_representative' => 'Gerencia General',
                'fecha_evento' => now()->addDays(20)->toDateString(),
                'fecha_emision' => now()->toDateString(),
                'fecha_vencimiento' => now()->addDays(7)->toDateString(),
                'subtotal' => 1652.54,
                'igv' => 297.46,
                'total' => 1950.00,
                'moneda' => 'PEN',
                'observaciones' => 'Pedido web preliminar demo registrado desde menú digital con Ley 29733.',
                'estado' => Contract::STATUS_DRAFT,
                'status' => Contract::STATUS_STR_PRELIMINAR,
                'source' => Contract::SOURCE_WEB,
                'tracking_code' => 'SHP-DEMO01',
                'guest_count' => 50,
                'event_type' => 'Boda Civil',
                'consent_version' => 'v1.0',
                'consent_accepted_at' => now(),
            ]
        );

        ContractItem::updateOrCreate(
            ['contract_id' => $preliminaryOrder->id, 'descripcion' => $package1->name],
            [
                'cantidad' => 1,
                'precio_unitario' => 1950.00,
                'subtotal' => 1950.00,
            ]
        );

        // -------------------------------------------------------------
        // 3. One Formalized Contract with Checklist & Installments (B4-B6)
        // -------------------------------------------------------------
        $clientCorp = Client::firstOrCreate(
            ['nro_documento' => '20609999991'],
            [
                'iddoc' => $docTypeRuc?->id ?? 2,
                'nombres' => 'CORPORACIÓN AMAZONAS DEMO S.A.C.',
                'telefono' => '942999888',
                'direccion' => 'Jr. Alegría Arias de Morey 450, Tarapoto',
            ]
        );

        $formalContract = Contract::updateOrCreate(
            ['contract_number' => 'CON-DEMO-2026-001'],
            [
                'preliminary_number' => 'PRE-DEMO-PREV-09',
                'title' => 'CONTRATO DE SERVICIO DE COCTELERÍA Y OPEN BAR',
                'idcliente' => $clientCorp->id,
                'idalmacen' => $warehouse->id,
                'provider_name' => $business?->razon_social ?? 'MYTEMS E.I.R.L.',
                'provider_document' => $business?->ruc ?? '20600000000',
                'provider_representative' => 'Gerencia General',
                'fecha_evento' => now()->addDays(15)->toDateString(),
                'fecha_emision' => now()->toDateString(),
                'fecha_vencimiento' => now()->addDays(30)->toDateString(),
                'subtotal' => 2542.37,
                'igv' => 457.63,
                'total' => 3000.00,
                'moneda' => 'PEN',
                'observaciones' => 'Contrato formalizado demo para evento corporativo de fin de año.',
                'estado' => Contract::STATUS_SIGNED,
                'status' => Contract::STATUS_STR_VIGENTE,
                'source' => Contract::SOURCE_INTERNO,
                'tracking_code' => 'SHP-DEMOCON',
                'guest_count' => 80,
                'event_type' => 'Aniversario Corporativo',
                'consent_version' => 'v1.0',
                'consent_accepted_at' => now(),
            ]
        );

        // Contract Items
        $cItem1 = ContractItem::updateOrCreate(
            ['contract_id' => $formalContract->id, 'descripcion' => 'SERVICIO DE BARRA LIBRE PREMIUM (80 INVITADOS)'],
            [
                'cantidad' => 1,
                'precio_unitario' => 2400.00,
                'subtotal' => 2400.00,
            ]
        );
        $cItem2 = ContractItem::updateOrCreate(
            ['contract_id' => $formalContract->id, 'descripcion' => 'ALQUILER DE CRISTALERÍA FINA TEMPLADA (200 PZAS)'],
            [
                'cantidad' => 1,
                'precio_unitario' => 600.00,
                'subtotal' => 600.00,
            ]
        );

        // Installments
        ContractInstallment::updateOrCreate(
            ['contract_id' => $formalContract->id, 'numero_cuota' => 1],
            [
                'descripcion' => 'Cuota 1 - Reserva 50%',
                'fecha_vencimiento' => now()->addDays(2)->toDateString(),
                'monto' => 1500.00,
                'porcentaje' => 50.00,
                'estado' => 0,
            ]
        );

        ContractInstallment::updateOrCreate(
            ['contract_id' => $formalContract->id, 'numero_cuota' => 2],
            [
                'descripcion' => 'Cuota 2 - Saldo Final 50%',
                'fecha_vencimiento' => now()->addDays(14)->toDateString(),
                'monto' => 1500.00,
                'porcentaje' => 50.00,
                'estado' => 0,
            ]
        );

        ContractInstallment::updateOrCreate(
            ['contract_id' => $formalContract->id, 'numero_cuota' => 3],
            [
                'descripcion' => 'Garantía por Cristalería y Menaje',
                'fecha_vencimiento' => now()->addDays(14)->toDateString(),
                'monto' => 500.00,
                'porcentaje' => 0.00,
                'estado' => 0,
                'observaciones' => 'Garantía reembolsable sujeta a checklist de retorno',
            ]
        );

        // Event Checklist
        $checklist = EventChecklist::updateOrCreate(
            ['codigo' => 'CHK-DEMO-001'],
            [
                'contract_id' => $formalContract->id,
                'titulo' => 'Checklist de Montaje y Retorno - Corporación Amazonas',
                'fecha_evento' => now()->addDays(15)->toDateString(),
                'hora_evento' => '19:00:00',
                'lugar_evento' => 'Centro de Convenciones Huallaga, Tarapoto',
                'responsable_montaje' => 'Jefe de Barra Shipibo',
                'responsable_desmontaje' => 'Supervisor de Logística',
                'estado' => 0, // 0=Planificado/Borrador
                'total_items' => 4,
                'items_llevados' => 0,
                'items_devueltos' => 0,
                'items_con_incidencia' => 0,
                'observaciones_salida' => 'Equipamiento revisado en almacén previo a salida.',
                'idusuario' => $user->id,
                'idalmacen' => $warehouse->id,
            ]
        );

        // Checklist Items
        EventChecklistItem::updateOrCreate(
            ['checklist_id' => $checklist->id, 'descripcion' => 'Copas Huracán de Cristal Templado'],
            [
                'contract_item_id' => $cItem2->id,
                'categoria' => 'Cristalería',
                'cantidad_planeada' => 100.00,
                'cantidad_llevada' => 0.00,
                'llevado' => false,
                'orden' => 1,
            ]
        );
        EventChecklistItem::updateOrCreate(
            ['checklist_id' => $checklist->id, 'descripcion' => 'Vasos Highball Tallados'],
            [
                'contract_item_id' => $cItem2->id,
                'categoria' => 'Cristalería',
                'cantidad_planeada' => 100.00,
                'cantidad_llevada' => 0.00,
                'llevado' => false,
                'orden' => 2,
            ]
        );
        EventChecklistItem::updateOrCreate(
            ['checklist_id' => $checklist->id, 'descripcion' => 'Barra Móvil Plegable de Madera Shipibo'],
            [
                'contract_item_id' => null,
                'categoria' => 'Mobiliario',
                'cantidad_planeada' => 1.00,
                'cantidad_llevada' => 0.00,
                'llevado' => false,
                'orden' => 3,
            ]
        );
        EventChecklistItem::updateOrCreate(
            ['checklist_id' => $checklist->id, 'descripcion' => 'Kit Utensilios Boston Shaker + Garnish Tongs'],
            [
                'contract_item_id' => null,
                'categoria' => 'Herramientas de Barra',
                'cantidad_planeada' => 2.00,
                'cantidad_llevada' => 0.00,
                'llevado' => false,
                'orden' => 4,
            ]
        );
    }
}
