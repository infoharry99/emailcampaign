<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemUtilityTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'email' => 'admin@thenexteck.com',
        ]);
    }

    public function test_artisan_migrate_route_executes_successfully()
    {
        $response = $this->actingAs($this->user)->get('/artisan/migrate');
        $response->assertStatus(200);
        $response->assertSee('php artisan migrate');
    }

    public function test_artisan_routes_list_executes_successfully()
    {
        $response = $this->actingAs($this->user)->get('/artisan/routes/list');
        $response->assertStatus(200);
        $response->assertSee('route:list');
    }

    public function test_artisan_routes_clear_executes_successfully()
    {
        $response = $this->actingAs($this->user)->get('/artisan/routes/clear');
        $response->assertStatus(200);
        $response->assertSee('route:clear');
    }

    public function test_artisan_run_whitelisted_command()
    {
        $response = $this->actingAs($this->user)->get('/artisan/run/optimize:clear');
        $response->assertStatus(200);
        $response->assertSee('optimize:clear');
    }

    public function test_artisan_run_disallows_unauthorized_arbitrary_commands()
    {
        $response = $this->actingAs($this->user)->get('/artisan/run/custom:dangerous_command');
        $response->assertStatus(400);
        $response->assertJsonFragment(['success' => false]);
    }

    public function test_artisan_routes_accessible_with_security_key()
    {
        $response = $this->get('/artisan/routes/clear?key=thenexteck2026');
        $response->assertStatus(200);
    }
}
