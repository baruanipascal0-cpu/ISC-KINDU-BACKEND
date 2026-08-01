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
}
