<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class WebCatalogTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminUser = User::first();
    }

    /**
     * Unauthenticated user cannot access web catalog.
     */
    public function test_guest_is_redirected_to_login_from_web_catalog(): void
    {
        $response = $this->get('/web-catalog');
        $response->assertRedirect(route('login'));

        $response2 = $this->get('/packages');
        $response2->assertRedirect(route('login'));
    }

    /**
     * Admin can access web catalog view via both /packages and /web-catalog.
     */
    public function test_admin_with_permission_can_access_web_catalog_view(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/web-catalog');
        $response->assertStatus(200);
        $response->assertSee('Catálogo Web (/pedido)');
        $response->assertSee('Configurar Paquete del Catálogo Web');

        $responseAlias = $this->actingAs($this->adminUser)->get('/packages');
        $responseAlias->assertStatus(200);
        $responseAlias->assertSee('Catálogo Web (/pedido)');
    }

    /**
     * DataTable feed returns rich web catalog columns.
     */
    public function test_packages_datatables_returns_web_catalog_columns(): void
    {
        $response = $this->actingAs($this->adminUser)->getJson(route('admin.packages.get'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'order_num',
                    'imagen_thumb',
                    'name',
                    'capacity',
                    'colors',
                    'pricing_summary',
                    'specifications',
                    'active',
                    'acciones',
                ],
            ],
        ]);
    }

    /**
     * Can create a new package with all web catalog details.
     */
    public function test_can_create_new_web_catalog_package_with_specifications_colors_and_capacity(): void
    {
        $payload = [
            'name' => 'PAQUETE TEST DIAMOND',
            'order_num' => '08',
            'badge_glasses' => '800 Vasos',
            'tagline' => 'La más exclusiva barra móvil con coctelería molecular',
            'color' => '#8a2be2',
            'light_bg' => '#f3e8ff',
            'price_mode' => 'tiered',
            'min_guests' => 150,
            'max_guests' => 300,
            'default_guests' => 200,
            'ideal_capacity' => '150 a 300 personas',
            'description' => 'Servicio premium completo de barra y catering.',
            'active' => 1,
            'is_barman' => 0,
            'image_preset' => 'img/packages/pkg-07.png',
            'features' => json_encode([
                '2 Bartenders + 2 asistentes',
                'Barra móvil iluminada',
                'Cristalería de cristal fino',
                'Hielo gourmet tallado',
                'Insumos premium',
            ]),
            'tiers' => json_encode([
                [
                    'from_guests' => 150,
                    'to_guests' => 300,
                    'price' => 6500.00,
                    'price_type' => 'total',
                ],
            ]),
            'items' => json_encode([]),
        ];

        $response = $this->actingAs($this->adminUser)->postJson(route('admin.packages.save'), $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => true,
        ]);

        $this->assertDatabaseHas('packages', [
            'name' => 'PAQUETE TEST DIAMOND',
            'order_num' => '08',
            'badge_glasses' => '800 Vasos',
            'color' => '#8a2be2',
            'light_bg' => '#f3e8ff',
            'default_guests' => 200,
            'ideal_capacity' => '150 a 300 personas',
        ]);

        $created = Package::where('name', 'PAQUETE TEST DIAMOND')->first();
        $this->assertNotNull($created);
        $this->assertCount(5, $created->features);
        $this->assertEquals('img/packages/pkg-07.png', $created->image);

        // Verify it dynamically appears on /pedido
        $publicOrderResponse = $this->get('/pedido');
        $publicOrderResponse->assertStatus(200);
        $publicOrderResponse->assertSee('TEST DIAMOND');
        $publicOrderResponse->assertSee('800 Vasos');
        $publicOrderResponse->assertSee('150 a 300 personas');
    }

    /**
     * Can update package and changes reflect dynamically.
     */
    public function test_can_update_package_and_reflects_dynamically(): void
    {
        $pkg = Package::first();
        $this->assertNotNull($pkg);

        $payload = [
            'id' => $pkg->id,
            'name' => $pkg->name,
            'order_num' => '99',
            'badge_glasses' => '999 Vasos Especiales',
            'tagline' => 'Tagline actualizado para pruebas',
            'color' => '#ff5722',
            'light_bg' => '#ffebe5',
            'price_mode' => $pkg->price_mode,
            'min_guests' => $pkg->min_guests,
            'max_guests' => $pkg->max_guests,
            'default_guests' => 125,
            'ideal_capacity' => 'Capacidad Personalizada 125',
            'description' => 'Descripción actualizada',
            'active' => 1,
            'features' => json_encode(['Característica Especial 1', 'Característica Especial 2']),
            'tiers' => json_encode([
                [
                    'from_guests' => $pkg->min_guests,
                    'to_guests' => $pkg->max_guests ?: ($pkg->min_guests + 50),
                    'price' => 4500.00,
                    'price_type' => 'total',
                ],
            ]),
        ];

        $response = $this->actingAs($this->adminUser)->postJson(route('admin.packages.save'), $payload);
        $response->assertStatus(200);

        $pkg->refresh();
        $this->assertEquals('99', $pkg->order_num);
        $this->assertEquals('999 Vasos Especiales', $pkg->badge_glasses);
        $this->assertEquals('#ff5722', $pkg->color);
        $this->assertEquals('Capacidad Personalizada 125', $pkg->ideal_capacity);

        // Check public order view reflects updated data
        $orderResponse = $this->get('/pedido');
        $orderResponse->assertStatus(200);
        $orderResponse->assertSee('999 Vasos Especiales');
        $orderResponse->assertSee('Capacidad Personalizada 125');
    }

    /**
     * Can toggle active status of package.
     */
    public function test_can_toggle_package_status(): void
    {
        $pkg = Package::first();
        $initial = $pkg->active;

        $response = $this->actingAs($this->adminUser)->postJson(route('admin.packages.toggle_status'), [
            'id' => $pkg->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => true,
            'active' => ! $initial,
        ]);

        $pkg->refresh();
        $this->assertEquals(! $initial, (bool) $pkg->active);
    }

    /**
     * Can delete a package.
     */
    public function test_can_delete_package(): void
    {
        $pkg = Package::create([
            'name' => 'PAQUETE BORRABLE',
            'price_mode' => 'fixed',
            'min_guests' => 50,
            'base_price' => 1000.00,
            'active' => 1,
        ]);

        $response = $this->actingAs($this->adminUser)->postJson(route('admin.packages.delete'), [
            'id' => $pkg->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => true]);

        $this->assertNull(Package::find($pkg->id));
    }
}
