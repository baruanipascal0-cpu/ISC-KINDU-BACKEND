<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\ContentBlock;
use App\Models\MediaFile;
use App\Models\NewsPost;
use App\Models\Publication;
use App\Models\StaffMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentNavigationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_news_and_api_exposes_it_by_slug(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'institution_code' => 'ISC_KINDU',
        ]);

        $this->actingAs($admin)->post('/admin/actualites', [
            'title' => 'Rentree academique ISC Kindu',
            'category' => 'Communique',
            'excerpt' => 'La rentree academique est annoncee.',
            'body' => 'Contenu complet de la rentree academique.',
            'image_url' => 'https://cdn.example.test/news/rentree.jpg',
            'image_alt' => 'Etudiants ISC Kindu',
            'published_at' => now()->format('Y-m-d H:i:s'),
            'is_published' => '1',
        ])->assertRedirect('/admin/actualites');

        $post = NewsPost::where('slug', 'rentree-academique-isc-kindu')->firstOrFail();

        $this->getJson('/api/news')
            ->assertOk()
            ->assertJsonFragment([
                'slug' => $post->slug,
                'image_url' => 'https://cdn.example.test/news/rentree.jpg',
                'image_disk' => 'external',
                'image_alt' => 'Etudiants ISC Kindu',
            ]);

        $this->getJson('/api/news/'.$post->slug)
            ->assertOk()
            ->assertJsonPath('data.slug', $post->slug)
            ->assertJsonPath('data.body', 'Contenu complet de la rentree academique.');
    }

    public function test_news_slug_is_preserved_when_title_changes_without_slug_input(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'institution_code' => 'ISC_KINDU',
        ]);
        $post = NewsPost::create([
            'title' => 'Titre initial',
            'slug' => 'titre-initial',
            'category' => 'Actualites',
            'body' => 'Texte initial.',
            'published_at' => now(),
            'is_published' => true,
        ]);

        $this->actingAs($admin)->put('/admin/actualites/'.$post->slug, [
            'title' => 'Titre modifie',
            'category' => 'Actualites',
            'body' => 'Texte modifie.',
            'published_at' => now()->format('Y-m-d H:i:s'),
            'is_published' => '1',
        ])->assertRedirect('/admin/actualites');

        $this->assertDatabaseHas('news_posts', [
            'id' => $post->id,
            'title' => 'Titre modifie',
            'slug' => 'titre-initial',
        ]);
    }

    public function test_news_recent_content_is_first_and_pagination_uses_requested_page_size(): void
    {
        foreach (range(1, 13) as $index) {
            NewsPost::create([
                'title' => 'Actualite '.$index,
                'slug' => 'actualite-'.$index,
                'category' => 'Actualites',
                'body' => 'Corps '.$index,
                'published_at' => now()->subDays($index),
                'is_published' => true,
            ]);
        }

        NewsPost::create([
            'title' => 'Actualite recente',
            'slug' => 'actualite-recente',
            'category' => 'Actualites',
            'body' => 'La plus recente.',
            'published_at' => now()->addMinute(),
            'is_published' => true,
        ]);

        $this->getJson('/api/news?per_page=12')
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'actualite-recente')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 12)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('meta.total', 14);

        $this->getJson('/api/news?per_page=12&page=2')
            ->assertOk()
            ->assertJsonPath('meta.current_page', 2);
    }

    public function test_event_detail_is_available_by_slug(): void
    {
        Event::create([
            'title' => 'Conference scientifique',
            'slug' => 'conference-scientifique',
            'description' => 'Detail complet de la conference.',
            'location' => 'Campus ISC Kindu',
            'starts_at' => now()->addDay(),
            'image_url' => 'https://cdn.example.test/events/conference.jpg',
            'image_disk' => 'external',
            'image_alt' => 'Conference ISC Kindu',
            'is_published' => true,
        ]);

        $this->getJson('/api/events/conference-scientifique')
            ->assertOk()
            ->assertJsonPath('data.slug', 'conference-scientifique')
            ->assertJsonPath('data.description', 'Detail complet de la conference.')
            ->assertJsonPath('data.image_url', 'https://cdn.example.test/events/conference.jpg');
    }

    public function test_publication_detail_and_persistent_image_url_are_available_by_slug(): void
    {
        Publication::create([
            'title' => 'Guide etudiant',
            'slug' => 'guide-etudiant',
            'type' => 'Ressource',
            'description' => 'Detail complet du guide.',
            'image_url' => 'https://cdn.example.test/publications/guide.jpg',
            'image_disk' => 'external',
            'image_alt' => 'Couverture du guide etudiant',
            'file_url' => 'https://cdn.example.test/publications/guide.pdf',
            'published_at' => now(),
            'is_published' => true,
        ]);

        $this->getJson('/api/publications?type=Ressource&per_page=12')
            ->assertOk()
            ->assertJsonFragment([
                'slug' => 'guide-etudiant',
                'image_url' => 'https://cdn.example.test/publications/guide.jpg',
                'image_disk' => 'external',
            ]);

        $this->getJson('/api/publications/guide-etudiant')
            ->assertOk()
            ->assertJsonPath('data.slug', 'guide-etudiant')
            ->assertJsonPath('data.description', 'Detail complet du guide.')
            ->assertJsonPath('data.file_url', 'https://cdn.example.test/publications/guide.pdf');
    }

    public function test_fee_schedule_api_exposes_only_published_fee_documents(): void
    {
        Publication::create([
            'title' => 'Echeancier des frais',
            'slug' => 'echeancier-des-frais',
            'type' => 'Frais',
            'description' => 'Frais academiques officiels.',
            'file_url' => 'https://cdn.example.test/frais/echeancier.pdf',
            'published_at' => now(),
            'is_published' => true,
        ]);

        Publication::create([
            'title' => 'Document interne',
            'slug' => 'document-interne',
            'type' => 'Document',
            'description' => 'Ne doit pas apparaitre dans les frais.',
            'published_at' => now(),
            'is_published' => true,
        ]);

        Publication::create([
            'title' => 'Frais brouillon',
            'slug' => 'frais-brouillon',
            'type' => 'Frais',
            'published_at' => now(),
            'is_published' => false,
        ]);

        $this->getJson('/api/fees')
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'echeancier-des-frais')
            ->assertJsonPath('data.0.file_url', 'https://cdn.example.test/frais/echeancier.pdf')
            ->assertJsonPath('meta.total', 1);

        $this->getJson('/api/fees/echeancier-des-frais')
            ->assertOk()
            ->assertJsonPath('data.type', 'Frais')
            ->assertJsonPath('data.description', 'Frais academiques officiels.');
    }

    public function test_unknown_slug_returns_404(): void
    {
        $this->getJson('/api/news/slug-inconnu')->assertNotFound();
        $this->getJson('/api/events/slug-inconnu')->assertNotFound();
        $this->getJson('/api/publications/slug-inconnu')->assertNotFound();
        $this->getJson('/api/fees/slug-inconnu')->assertNotFound();
    }

    public function test_gallery_teachers_search_and_institution_blocks_are_available(): void
    {
        ContentBlock::create([
            'block_group' => 'home_service',
            'key' => 'home_service.empty_api_test',
            'title' => null,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        MediaFile::create([
            'name' => 'Campus Kindu',
            'slug' => 'campus-kindu',
            'collection' => 'gallery',
            'caption' => 'Photo du campus.',
            'alt_text' => 'Campus ISC Kindu',
            'path' => 'https://cdn.example.test/gallery/campus.jpg',
            'disk' => 'external',
            'mime_type' => 'image/jpeg',
            'size' => 12345,
            'is_published' => true,
            'published_at' => now(),
        ]);

        StaffMember::create([
            'name' => 'Professeur Demo',
            'slug' => 'professeur-demo',
            'title' => 'Professeur',
            'role' => 'enseignant',
            'department' => 'Informatique de gestion',
            'is_active' => true,
        ]);

        $this->getJson('/api/institution/blocks')
            ->assertOk()
            ->assertJsonFragment(['key' => 'home_service.empty_api_test']);

        $this->getJson('/api/gallery')
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'campus-kindu')
            ->assertJsonPath('data.0.url', 'https://cdn.example.test/gallery/campus.jpg');

        $this->getJson('/api/gallery/campus-kindu')
            ->assertOk()
            ->assertJsonPath('data.caption', 'Photo du campus.');

        $this->getJson('/api/teachers')
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'professeur-demo');

        $this->getJson('/api/search?q=professeur')
            ->assertOk()
            ->assertJsonFragment([
                'type' => 'staff',
                'title' => 'Professeur Demo',
            ]);

        $this->getJson('/api/site/content-map')
            ->assertOk()
            ->assertJsonPath('data.gallery.0.slug', 'campus-kindu')
            ->assertJsonPath('data.teachers.0.slug', 'professeur-demo');
    }

    public function test_publication_aliases_cover_documents_research_alumni_and_opportunities(): void
    {
        foreach ([
            ['Guide etudiant', 'guide-etudiant', 'Document'],
            ['Article scientifique', 'article-scientifique', 'Article'],
            ['Offre de stage', 'offre-de-stage', 'Stage'],
            ['Ancien etudiant', 'ancien-etudiant', 'Alumni'],
        ] as [$title, $slug, $type]) {
            Publication::create([
                'title' => $title,
                'slug' => $slug,
                'type' => $type,
                'description' => 'Contenu '.$type,
                'published_at' => now(),
                'is_published' => true,
            ]);
        }

        $this->getJson('/api/documents')
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'guide-etudiant');

        $this->getJson('/api/research')
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'article-scientifique');

        $this->getJson('/api/opportunities')
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'offre-de-stage');

        $this->getJson('/api/alumni')
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'ancien-etudiant');
    }

    public function test_existing_public_routes_still_respond(): void
    {
        foreach ([
            '/api/site/settings',
            '/api/site/content-map',
            '/api/search',
            '/api/home/cards',
            '/api/home/statistics',
            '/api/sections',
            '/api/teachers',
            '/api/news/categories',
            '/api/news',
            '/api/events',
            '/api/gallery',
            '/api/documents',
            '/api/research',
            '/api/recherche-societe',
            '/api/opportunities',
            '/api/opportunites',
            '/api/emplois',
            '/api/alumni',
            '/api/publications',
            '/api/fees',
            '/api/frais',
            '/api/diplomas',
            '/api/palmares',
        ] as $route) {
            $this->getJson($route)->assertOk();
        }
    }

    public function test_public_opportunity_forms_create_draft_publications_for_admin_review(): void
    {
        $this->postJson('/api/opportunites', [
            'title' => 'Stage en comptabilite',
            'organization' => 'Partenaire ISC',
            'type' => 'Stage',
            'deadline' => now()->addMonth()->toDateString(),
            'apply_contact' => 'stage@example.test',
            'summary' => 'Stage propose aux etudiants finalistes.',
        ])->assertCreated()
            ->assertJsonPath('data.type', 'Stage')
            ->assertJsonPath('data.is_published', false);

        $this->assertDatabaseHas('publications', [
            'title' => 'Stage en comptabilite',
            'type' => 'Stage',
            'is_published' => false,
        ]);
    }
}
