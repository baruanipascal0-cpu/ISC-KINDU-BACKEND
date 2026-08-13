<?php

namespace Tests\Feature;

use App\Models\ContentBlock;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
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
            '/admin/navigation',
            '/admin/blocs-site',
            '/admin/medias',
            '/admin/sections',
            '/admin/enseignants',
            '/admin/registre-inscriptions',
            '/admin/paiements-etudiants',
            '/admin/documents-etudiants',
            '/admin/notifications-etudiants',
            '/admin/publications?type=Diplome',
            '/admin/publications?type=Ressource',
            '/admin/publications?type=Alumni',
            '/admin/commentaires-etudiants',
            '/admin/production',
        ] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_admin_can_update_institution_settings(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();

        $this->actingAs($admin)
            ->put('/admin/parametres', [
                'settings' => [
                    'institution.email' => 'contact@isc-kindu.ac.cd',
                    'institution.phone' => '+243 999 000 111',
                    'institution.address' => 'Avenue du Commerce, Kindu',
                    'admissions.academic_year' => '2026-2027',
                    'social.facebook_url' => null,
                    'social.youtube_url' => null,
                    'social.email' => '',
                ],
            ])
            ->assertRedirect();

        $settings = $this->getJson('/api/site/settings')
            ->assertOk()
            ->json('data');

        $this->assertSame('contact@isc-kindu.ac.cd', $settings['institution.email']);
        $this->assertSame('+243 999 000 111', $settings['institution.phone']);
        $this->assertSame('Avenue du Commerce, Kindu', $settings['institution.address']);
        $this->assertSame('', $settings['social.facebook_url']);
        $this->assertSame('', $settings['social.youtube_url']);
        $this->assertSame('', $settings['social.email']);
        $this->assertFalse($settings['admissions.is_open']);
    }

    public function test_seeders_do_not_overwrite_admin_content_after_redeploy(): void
    {
        $page = Page::where('slug', 'institution')->firstOrFail();
        $page->update([
            'title' => 'Institution modifiee',
            'excerpt' => 'Resume admin conserve.',
            'image_url' => 'https://cdn.example.test/pages/institution-admin.jpg',
        ]);

        $slide = ContentBlock::where('key', 'home_slide.welcome')->firstOrFail();
        $slide->update([
            'title' => 'Banniere admin',
            'body' => 'Texte de banniere publie depuis admin.',
            'image_url' => 'https://cdn.example.test/slides/admin.jpg',
        ]);

        SiteSetting::where('key', 'institution.phone')->firstOrFail()
            ->update(['value' => '+243 900 111 222']);

        $this->seed();

        $this->assertDatabaseHas('pages', [
            'id' => $page->id,
            'title' => 'Institution modifiee',
            'excerpt' => 'Resume admin conserve.',
            'image_url' => 'https://cdn.example.test/pages/institution-admin.jpg',
        ]);

        $this->assertDatabaseHas('content_blocks', [
            'id' => $slide->id,
            'title' => 'Banniere admin',
            'body' => 'Texte de banniere publie depuis admin.',
            'image_url' => 'https://cdn.example.test/slides/admin.jpg',
        ]);

        $this->assertSame(
            '+243 900 111 222',
            SiteSetting::where('key', 'institution.phone')->firstOrFail()->value
        );
    }

    public function test_public_detail_pages_render_with_base_tag(): void
    {
        $this->get('/actualites/debut')
            ->assertOk()
            ->assertSee('<base href="/">', false);

        $this->get('/publications/debut')
            ->assertOk()
            ->assertSee('<base href="/">', false);

        $this->get('/evenements/debut')
            ->assertOk()
            ->assertSee('<base href="/">', false);
    }

    public function test_institution_pages_keep_their_own_frontend_template(): void
    {
        $this->get('/presentation-de-lisc-kindu.html')
            ->assertOk()
            ->assertDontSee('Articles de recherche');
    }

    public function test_public_storage_files_are_served_when_reaching_laravel(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('content/publications/bulletin.pdf', '%PDF-1.4 test');

        $this->get('/storage/content/publications/bulletin.pdf')
            ->assertOk()
            ->assertHeader('content-disposition', 'inline; filename=bulletin.pdf')
            ->assertStreamedContent('%PDF-1.4 test');
    }
}
