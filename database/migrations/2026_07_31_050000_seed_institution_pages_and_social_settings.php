<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach ($this->settings() as [$key, $value, $type, $group]) {
            DB::table('site_settings')->updateOrInsert(
                ['key' => $key],
                [
                    'value' => json_encode($value),
                    'type' => $type,
                    'group' => $group,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        foreach ($this->pages() as [$title, $slug]) {
            DB::table('pages')->updateOrInsert(
                ['slug' => $slug],
                [
                    'title' => $title,
                    'excerpt' => 'Contenu a completer depuis l espace administrateur.',
                    'body' => 'A completer.',
                    'image_url' => '/images/site/photo-1.jpg',
                    'is_published' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
    }

    private function settings(): array
    {
        return [
            ['social.facebook_url', '', 'url', 'reseaux_sociaux'],
            ['social.x_url', '', 'url', 'reseaux_sociaux'],
            ['social.linkedin_url', '', 'url', 'reseaux_sociaux'],
            ['social.youtube_url', '', 'url', 'reseaux_sociaux'],
            ['social.email', 'contact@isc-kindu.test', 'email', 'reseaux_sociaux'],
        ];
    }

    private function pages(): array
    {
        return [
            ['Presentation de l ISC KINDU', 'presentation-de-lisc-kindu'],
            ['Conseil d administration', 'conseil-administration'],
            ['Le Directeur General', 'directeur-general'],
            ['Comite de gestion', 'comite-de-gestion'],
            ['Conseil de section', 'conseil-de-section'],
            ['Conseil de departement', 'conseil-de-departement'],
            ['Textes legaux et reglement de l ESU', 'textes-legaux-et-reglementaires-de-lesu'],
            ['Membre du comite de gestion', 'membre-comite-gestion'],
            ['Bibliotheques', 'bibliotheques'],
            ['Comment reussir ses etudes', 'comment-reussir-ses-etudes'],
            ['Articles', 'articles'],
            ['Centre et Institut de recherche', 'centre-et-instituts-de-recherche'],
            ['Nos theses', 'nos-theses'],
            ['In memoriam', 'in-memoriam'],
        ];
    }
};
