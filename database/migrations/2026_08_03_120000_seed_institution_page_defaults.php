<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach ($this->pages() as $page) {
            $existing = DB::table('pages')->where('slug', $page['slug'])->first();

            if (! $existing) {
                DB::table('pages')->insert($page + [
                    'is_published' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                continue;
            }

            $payload = [
                'title' => $page['title'],
                'is_published' => true,
                'updated_at' => $now,
            ];

            if ($this->shouldReplaceText($existing->excerpt ?? null)) {
                $payload['excerpt'] = $page['excerpt'];
            }

            if ($this->shouldReplaceText($existing->body ?? null)) {
                $payload['body'] = $page['body'];
            }

            if ($this->shouldReplaceImage($existing->image_url ?? null)) {
                $payload['image_url'] = $page['image_url'];
            }

            DB::table('pages')->where('id', $existing->id)->update($payload);
        }
    }

    public function down(): void
    {
    }

    private function shouldReplaceText(?string $value): bool
    {
        $text = trim(strip_tags((string) $value));

        return $text === ''
            || in_array($text, [
                'A completer.',
                'A completer',
                'Contenu a completer depuis l espace administrateur.',
                'Page institutionnelle a completer si elle est necessaire.',
                'Ce contenu reste disponible dans le backend, mais les anciens liens lies a un autre etablissement sont masques sur le site.',
            ], true)
            || str_contains($text, 'Information a completer');
    }

    private function shouldReplaceImage(?string $value): bool
    {
        $path = trim((string) $value);

        return $path === '' || $path === '/images/site/photo-1.jpg';
    }

    private function pages(): array
    {
        return [
            [
                'title' => 'Presentation de l ISC KINDU',
                'slug' => 'presentation-de-lisc-kindu',
                'excerpt' => 'Identite, mission et organisation generale de l Institut Superieur de Commerce de Kindu.',
                'body' => "L ISC KINDU presente ici son histoire, sa mission de formation et sa contribution au developpement du Maniema.\n\nCette page peut etre enrichie depuis l espace administrateur avec les informations officielles de l institution.",
                'image_url' => '/images/site/photo-1.jpg',
            ],
            [
                'title' => 'Conseil d administration',
                'slug' => 'conseil-administration',
                'excerpt' => 'Instance d orientation et de suivi de la gouvernance institutionnelle.',
                'body' => "Le conseil d administration accompagne les grandes orientations de l ISC KINDU et veille a la bonne gouvernance de l institution.\n\nLes decisions, attributions et informations officielles peuvent etre completees depuis l espace administrateur.",
                'image_url' => '/images/site/photo-2.jpg',
            ],
            [
                'title' => 'Le Directeur General',
                'slug' => 'directeur-general',
                'excerpt' => 'Autorite chargee de la direction et de la coordination generale de l ISC KINDU.',
                'body' => "Le Directeur General assure la conduite institutionnelle, academique et administrative de l ISC KINDU.\n\nCette page est prevue pour presenter le mot, le profil et les informations officielles de la direction generale.",
                'image_url' => '/images/site/photo-3.jpg',
            ],
            [
                'title' => 'Comite de gestion',
                'slug' => 'comite-de-gestion',
                'excerpt' => 'Equipe responsable de la gestion quotidienne de l institution.',
                'body' => "Le comite de gestion coordonne les services academiques, administratifs, financiers et scientifiques de l ISC KINDU.\n\nLes membres, fonctions et communiques officiels peuvent etre mis a jour dans le backend.",
                'image_url' => '/images/site/photo-4.jpg',
            ],
            [
                'title' => 'Conseil de section',
                'slug' => 'conseil-de-section',
                'excerpt' => 'Cadre d organisation pedagogique et scientifique des sections.',
                'body' => "Le conseil de section suit l organisation des enseignements, l encadrement des etudiants et la qualite academique dans chaque section.\n\nCette page donne un espace autonome aux informations propres aux sections.",
                'image_url' => '/images/site/photo-5.jpg',
            ],
            [
                'title' => 'Conseil de departement',
                'slug' => 'conseil-de-departement',
                'excerpt' => 'Cadre de coordination des departements et des filieres.',
                'body' => "Le conseil de departement accompagne les activites pedagogiques, les programmes et l encadrement scientifique des filieres.\n\nLes informations propres a chaque departement peuvent etre completees depuis l admin.",
                'image_url' => '/images/site/photo-6.jpg',
            ],
            [
                'title' => 'Textes legaux et reglementaires de l ESU',
                'slug' => 'textes-legaux-et-reglementaires-de-lesu',
                'excerpt' => 'Documents de reference pour le fonctionnement academique et administratif.',
                'body' => "Cette page regroupe les textes, reglements et references utiles au fonctionnement de l enseignement superieur et universitaire.\n\nLes documents officiels peuvent etre ajoutes comme publications ou liens depuis le backend.",
                'image_url' => '/images/site/photo-7.jpg',
            ],
            [
                'title' => 'Membre du comite de gestion',
                'slug' => 'membre-comite-gestion',
                'excerpt' => 'Presentation des responsables membres du comite de gestion.',
                'body' => "Cette page presente les membres du comite de gestion, leurs fonctions et les informations institutionnelles associees.\n\nElle reste modifiable depuis l espace administrateur pour garder les donnees a jour.",
                'image_url' => '/images/site/photo-8.jpg',
            ],
            [
                'title' => 'Plan strategique',
                'slug' => 'plan-strategique',
                'excerpt' => 'Orientations prioritaires pour le developpement de l ISC KINDU.',
                'body' => "Le plan strategique presente les axes de developpement de l ISC KINDU: qualite academique, recherche, gouvernance, numerisation et ouverture au milieu professionnel.\n\nLes objectifs, activites et documents de reference peuvent etre completes depuis le backend.",
                'image_url' => '/images/site/photo-9.jpg',
            ],
        ];
    }
};
