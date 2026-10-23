<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Contract;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PreliminaryOrderAdminTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;

    protected Contract $samplePreliminary;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::first();

        // Create a test preliminary contract
        $docTypeId = \App\Models\IdentityDocumentType::where('codigo', '0')->value('id') ?? 1;
        $client = Client::firstOrCreate(
            ['telefono' => '944332211'],
            [
                'iddoc' => $docTypeId,
                'nro_documento' => 'SD-944332211',
                'nombres' => 'MARIA FERNANDA TEST',
                'direccion' => 'Tarapoto, San Martín',
                'estado' => 1,
            ]
        );

        $this->samplePreliminary = Contract::create([
            'contract_number' => Contract::generatePreliminaryNumber(),
            'tracking_code' => Contract::generateTrackingCode(),
            'idcliente' => $client->id,
            'provider_name' => 'MYTEMS E.I.R.L.',
            'provider_document' => '20600000000',
            'provider_representative' => 'Gerencia General',
            'fecha_emision' => now()->toDateString(),
            'fecha_vencimiento' => now()->addDays(7)->toDateString(),
            'source' => Contract::SOURCE_WEB,
            'status' => Contract::STATUS_STR_PRELIMINAR,
            'estado' => Contract::STATUS_DRAFT,
            'title' => 'SOLICITUD WEB - MARIA FERNANDA TEST',
            'subtotal' => 3559.32,
            'igv' => 640.68,
            'total' => 4200.00,
            'fecha_evento' => now()->addDays(20)->toDateString(),
            'event_type' => 'Boda',
            'guest_count' => 120,
            'observaciones' => 'Solicitud generada vía catálogo digital web.',
        ]);
    }

    /**
     * Unauthenticated user cannot access preliminary orders queue.
     */
    public function test_guest_is_redirected_from_preliminary_orders_queue(): void
    {
        $response = $this->get('/contracts/preliminary-orders');
        $response->assertRedirect(route('login'));
    }

    /**
     * Authorized admin can view preliminary orders queue page.
     */
    public function test_admin_can_access_preliminary_orders_queue(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/contracts/preliminary-orders');
        $response->assertStatus(200);
        $response->assertSee('Cola de Pedidos Web');
        $response->assertSee('Preliminares Pendientes');
    }

    /**
     * Admin can fetch DataTables list of preliminary orders.
     */
    public function test_admin_can_fetch_preliminary_orders_data(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/contracts/preliminary-orders/get');

        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
        $this->assertTrue(count($response->json('data')) > 0);
    }

    /**
     * Admin can view details of a preliminary contract.
     */
    public function test_admin_can_view_preliminary_order_detail(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/contracts/preliminary-orders/detail?id='.$this->samplePreliminary->id);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => true,
            'contract' => [
                'id' => $this->samplePreliminary->id,
                'status' => Contract::STATUS_STR_PRELIMINAR,
                'source' => Contract::SOURCE_WEB,
            ],
        ]);
    }

    /**
     * Admin can convert a preliminary order to a draft contract (CON-YYYY-XXXX).
     */
    public function test_admin_can_convert_preliminary_order_to_draft(): void
    {
        $initialNumber = $this->samplePreliminary->contract_number;
        $this->assertStringStartsWith('PRE-', $initialNumber);

        $response = $this->actingAs($this->adminUser)
            ->postJson('/contracts/preliminary-orders/convert-to-draft', [
                'id' => $this->samplePreliminary->id,
            ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => true]);

        $newNumber = $response->json('new_number');
        $this->assertStringStartsWith('CON-', $newNumber);

        $this->samplePreliminary->refresh();
        $this->assertEquals(Contract::STATUS_STR_BORRADOR, $this->samplePreliminary->status);
        $this->assertEquals($newNumber, $this->samplePreliminary->contract_number);
        $this->assertStringContainsString('Convertido desde pedido web preliminar', $this->samplePreliminary->observaciones);
    }

    /**
     * Re-converting an already converted contract is rejected with 422.
     */
    public function test_cannot_reconvert_already_converted_contract(): void
    {
        // First conversion
        $this->actingAs($this->adminUser)
            ->postJson('/contracts/preliminary-orders/convert-to-draft', [
                'id' => $this->samplePreliminary->id,
            ]);

        // Second conversion attempt
        $response = $this->actingAs($this->adminUser)
            ->postJson('/contracts/preliminary-orders/convert-to-draft', [
                'id' => $this->samplePreliminary->id,
            ]);

        $response->assertStatus(422);
        $response->assertJson(['status' => false]);
    }
}
