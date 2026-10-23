<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Contract;
use App\Models\Package;
use App\Models\PackagePriceTier;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PublicOrderTest extends TestCase
{
    use DatabaseTransactions;

    protected Package $samplePackage;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure a test package with a tier exists in database
        $this->samplePackage = Package::firstOrCreate(
            ['name' => 'PAQUETE TEST FEATURE'],
            [
                'description' => 'Paquete para pruebas automatizadas',
                'min_guests' => 100,
                'max_guests' => 180,
                'price_mode' => 'tiered',
                'base_price' => 4200.00,
                'active' => true,
            ]
        );

        PackagePriceTier::firstOrCreate(
            [
                'package_id' => $this->samplePackage->id,
                'from_guests' => 100,
                'to_guests' => 180,
            ], [
                'price' => 4200.00,
                'price_type' => 'total',
            ]
        );
    }

    /**
     * Test public menu landing page is accessible.
     */
    public function test_public_menu_page_is_accessible(): void
    {
        $response = $this->get('/pedido');
        $response->assertStatus(200);
        $response->assertSee('Cotiza tu Barra Móvil');
        $response->assertSee('Ley N° 29733');
    }

    /**
     * Test public routes apply strict security headers.
     */
    public function test_public_menu_contains_security_headers(): void
    {
        $response = $this->get('/pedido');
        $response->assertStatus(200);
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
    }

    /**
     * Test valid preliminary order creation.
     */
    public function test_creates_valid_preliminary_order_and_contract(): void
    {
        $payload = [
            'nombre' => 'Carlos Mendoza',
            'telefono' => '942123456',
            'package_id' => $this->samplePackage->id,
            'guests' => 120,
            'fecha_evento' => now()->addDays(20)->toDateString(),
            'event_type' => 'Boda',
            'consent' => '1',
            '_hp_website' => '', // Honeypot empty
        ];

        $response = $this->postJson('/pedido/confirmar', $payload);
        $response->assertStatus(200);
        $response->assertJson(['status' => true]);
        $response->assertJsonStructure(['status', 'msg', 'tracking_code', 'redirect_url']);

        $trackingCode = $response->json('tracking_code');

        // Assert contract was created with PRE- numbering and status=preliminar
        $this->assertDatabaseHas('contracts', [
            'tracking_code' => $trackingCode,
            'status' => 'preliminar',
            'source' => 'web',
            'guest_count' => 120,
            'total' => 4200.00,
        ]);

        $contract = Contract::where('tracking_code', $trackingCode)->first();
        $this->assertNotNull($contract);
        $this->assertStringStartsWith('PRE-', $contract->contract_number);
        $this->assertEquals('v1.0', $contract->consent_version);
        $this->assertNotNull($contract->consent_accepted_at);
        $this->assertGreaterThan(0, $contract->items()->count());

        // Assert client record created with normalized phone
        $this->assertDatabaseHas('clients', [
            'telefono' => '942123456',
            'nombres' => 'CARLOS MENDOZA',
        ]);
    }

    /**
     * Test simulation endpoint validates payload via FormRequest.
     */
    public function test_simulate_endpoint_validates_payload(): void
    {
        $response = $this->postJson('/pedido/simular', [
            'package_id' => $this->samplePackage->id,
            'guests' => 0,
        ]);
        $response->assertStatus(422);
        $response->assertJson(['status' => false]);
    }

    /**
     * Test rejection of invalid phone numbers (must be 9 digits starting with 9).
     */
    public function test_rejects_invalid_phone_numbers(): void
    {
        $invalidPhones = [
            '12345',         // Too short
            '888123456',     // Doesn't start with 9
            '98765432101',   // Too long without prefix
            'abcdefghi',     // Letters
        ];

        foreach ($invalidPhones as $phone) {
            $payload = [
                'nombre' => 'Cliente Prueba',
                'telefono' => $phone,
                'package_id' => $this->samplePackage->id,
                'guests' => 120,
                'fecha_evento' => now()->addDays(10)->toDateString(),
                'consent' => '1',
            ];

            $response = $this->postJson('/pedido/confirmar', $payload);
            $response->assertStatus(422);
            $response->assertJson(['status' => false]);
        }
    }

    /**
     * Test honeypot field triggers immediate rejection.
     */
    public function test_honeypot_triggers_rejection(): void
    {
        $payload = [
            'nombre' => 'Bot Spammer',
            'telefono' => '987654321',
            'package_id' => $this->samplePackage->id,
            'guests' => 120,
            'fecha_evento' => now()->addDays(10)->toDateString(),
            'consent' => '1',
            '_hp_website' => 'http://spam-link.ru', // Bot filled honeypot
        ];

        $response = $this->postJson('/pedido/confirmar', $payload);
        $response->assertStatus(422);
        $response->assertJson(['status' => false, 'msg' => 'Solicitud inválida detectada.']);

        $this->assertDatabaseMissing('contracts', [
            'observaciones' => 'Solicitud generada vía catálogo digital web.',
            'event_type' => 'Bot Spammer',
        ]);
    }

    /**
     * Test server completely ignores price manipulation sent from browser.
     */
    public function test_ignores_price_manipulation_in_request(): void
    {
        $payload = [
            'nombre' => 'Hacker Intento',
            'telefono' => '999888777',
            'package_id' => $this->samplePackage->id,
            'guests' => 150,
            'fecha_evento' => now()->addDays(30)->toDateString(),
            'consent' => '1',
            // Manipulated prices sent by malicious user
            'total' => 1.00,
            'subtotal' => 0.50,
            'price' => 0.01,
            'base_price' => 0.00,
        ];

        $response = $this->postJson('/pedido/confirmar', $payload);
        $response->assertStatus(200);
        $trackingCode = $response->json('tracking_code');

        $contract = Contract::where('tracking_code', $trackingCode)->first();
        $this->assertNotNull($contract);

        // Server calculated the real price: S/ 4,200.00, completely ignoring S/ 1.00
        $this->assertEquals(4200.00, (float) $contract->total);
        $this->assertNotEquals(1.00, (float) $contract->total);
    }

    /**
     * Test client deduplication based on normalized phone number.
     */
    public function test_client_deduplication_by_normalized_phone(): void
    {
        $initialClientsCount = Client::where('telefono', '955112233')->count();
        $this->assertEquals(0, $initialClientsCount);

        // First order with +51 prefix
        $payload1 = [
            'nombre' => 'Ana Torres Inicial',
            'telefono' => '+51955112233',
            'package_id' => $this->samplePackage->id,
            'guests' => 110,
            'fecha_evento' => now()->addDays(15)->toDateString(),
            'consent' => '1',
        ];

        $res1 = $this->postJson('/pedido/confirmar', $payload1);
        $res1->assertStatus(200);

        $this->assertEquals(1, Client::where('telefono', '955112233')->count());
        $client = Client::where('telefono', '955112233')->first();
        $this->assertEquals('ANA TORRES INICIAL', $client->nombres);

        // Second order with plain 9 digits
        $payload2 = [
            'nombre' => 'Ana Torres Actualizada',
            'telefono' => '955112233',
            'package_id' => $this->samplePackage->id,
            'guests' => 140,
            'fecha_evento' => now()->addDays(25)->toDateString(),
            'consent' => '1',
        ];

        $res2 = $this->postJson('/pedido/confirmar', $payload2);
        $res2->assertStatus(200);

        // Still only 1 client record exists with that phone!
        $this->assertEquals(1, Client::where('telefono', '955112233')->count());
        $updatedClient = Client::where('telefono', '955112233')->first();
        $this->assertEquals($client->id, $updatedClient->id);
        $this->assertEquals('ANA TORRES ACTUALIZADA', $updatedClient->nombres);
    }

    /**
     * Test rate limiting throttles excessive submissions.
     */
    public function test_rate_limiting_throttles_excessive_submissions(): void
    {
        $payload = [
            'nombre' => 'Prueba Throttle',
            'telefono' => '911222333',
            'package_id' => $this->samplePackage->id,
            'guests' => 120,
            'fecha_evento' => now()->addDays(10)->toDateString(),
            'consent' => '1',
        ];

        // Send 5 permitted requests
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/pedido/confirmar', $payload);
        }

        // 6th request should be throttled with HTTP 429 Too Many Requests
        $response = $this->postJson('/pedido/confirmar', $payload);
        $response->assertStatus(429);
    }
}
