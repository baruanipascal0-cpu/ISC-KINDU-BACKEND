<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAndPublicPagesTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_admin_core_pages_render(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();

        foreach ([
            '/admin',
            '/admin/parametres',
            '/admin/blocs-site',
            '/admin/sections',
            '/admin/registre-inscriptions',
            '/admin/publications?type=Diplome',
            '/admin/publications?type=Ressource',
            '/admin/publications?type=Alumni',
            '/admin/commentaires-etudiants',
            '/admin/production',
        ] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_public_detail_pages_render_with_base_tag(): void
    {
        $this->get('/actualites/debut')
            ->assertOk()
            ->assertSee('<base href="/">', false);

        $this->get('/publications/debut')
            ->assertOk()
            ->assertSee('<base href="/">', false);
    }
}
