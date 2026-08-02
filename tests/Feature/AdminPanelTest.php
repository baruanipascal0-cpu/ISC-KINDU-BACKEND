<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_login_and_dashboard(): void
    {
        $this->seed();

        $this->get('/admin/login')->assertOk()->assertSee('Espace administrateur');

        $admin = User::where('email', 'admin@isc-kindu.test')->firstOrFail();

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Tableau de bord')
            ->assertSee('Actualites');
    }

    public function test_admin_login_keeps_secure_urls_behind_render_proxy(): void
    {
        $this->withHeaders([
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => 'isc-kindu-backend.onrender.com',
            'X-Forwarded-Port' => '443',
        ])->get('/admin/login')
            ->assertOk()
            ->assertSee('https://isc-kindu-backend.onrender.com/admin-assets/admin.css', false)
            ->assertSee('action="https://isc-kindu-backend.onrender.com/admin/login"', false);
    }
}
