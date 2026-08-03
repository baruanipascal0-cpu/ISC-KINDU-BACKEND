<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\NewsPost;
use App\Models\Publication;
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

    public function test_unknown_slug_returns_404(): void
    {
        $this->getJson('/api/news/slug-inconnu')->assertNotFound();
        $this->getJson('/api/events/slug-inconnu')->assertNotFound();
        $this->getJson('/api/publications/slug-inconnu')->assertNotFound();
    }

    public function test_existing_public_routes_still_respond(): void
    {
        foreach ([
            '/api/site/settings',
            '/api/home/cards',
            '/api/home/statistics',
            '/api/sections',
            '/api/news/categories',
            '/api/news',
            '/api/events',
            '/api/publications',
        ] as $route) {
            $this->getJson($route)->assertOk();
        }
    }
}
