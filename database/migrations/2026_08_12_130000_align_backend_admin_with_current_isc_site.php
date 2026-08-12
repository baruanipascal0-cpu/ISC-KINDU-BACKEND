<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->settings();
        $this->menu();
        $this->pages();
        $this->sectionsAndPrograms();
        $this->institutionServiceBlocks();
    }

    public function down(): void
    {
    }

    private function settings(): void
    {
        $now = now();

        foreach ([
            'institution.email' => ['info@isc-kindu.ac.cd', 'text', 'general'],
            'institution.phone' => ['+243 825 558 366', 'text', 'general'],
            'institution.address' => ['05, Av. Kindu, Kasuku, Kindu, Maniema/RDC', 'text', 'general'],
        ] as $key => [$value, $type, $group]) {
            DB::table('site_settings')->updateOrInsert(
                ['key' => $key],
                [
                    'value' => json_encode($value),
                    'type' => $type,
                    'group' => $group,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    private function menu(): void
    {
        $now = now();
        $menu = config('isc_site.menu', []);
        $parents = [];

        foreach (($menu['parents'] ?? []) as [$label, $url, $order]) {
            DB::table('menu_items')->updateOrInsert(
                ['location' => 'main', 'label' => $label],
                [
                    'parent_id' => null,
                    'url' => $url,
                    'sort_order' => $order,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            $parents[$label] = DB::table('menu_items')
                ->where('location', 'main')
                ->where('label', $label)
                ->value('id');
        }

        foreach (($menu['children'] ?? []) as $parentLabel => $items) {
            foreach ($items as [$label, $url, $order]) {
                DB::table('menu_items')->updateOrInsert(
                    ['location' => 'main', 'label' => $label],
                    [
                        'parent_id' => $parents[$parentLabel] ?? null,
                        'url' => $url,
                        'sort_order' => $order,
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }

        foreach (($menu['flat'] ?? []) as [$location, $label, $url, $order]) {
            DB::table('menu_items')->updateOrInsert(
                ['location' => $location, 'label' => $label],
                [
                    'parent_id' => null,
                    'url' => $url,
                    'sort_order' => $order,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        DB::table('menu_items')
            ->where('url', 'like', '%travailler-a-isig%')
            ->update([
                'url' => DB::raw("REPLACE(url, 'travailler-a-isig', 'travailler-a-isc')"),
                'updated_at' => $now,
            ]);
    }

    private function pages(): void
    {
        $now = now();

        foreach (config('isc_site.pages', []) as [$title, $slug, $excerpt, $imageUrl]) {
            DB::table('pages')->updateOrInsert(
                ['slug' => $slug],
                [
                    'title' => $title,
                    'excerpt' => $excerpt,
                    'body' => 'Contenu a completer depuis l espace administrateur.',
                    'image_url' => $imageUrl,
                    'is_published' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    private function sectionsAndPrograms(): void
    {
        $now = now();
        $sectionSlugs = [];

        foreach (config('isc_site.academic_structure', []) as $index => $section) {
            $sectionSlugs[] = $section['slug'];

            DB::table('sections')->updateOrInsert(
                ['slug' => $section['slug']],
                [
                    'name' => $section['name'],
                    'description' => $section['description'],
                    'sort_order' => $index + 1,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            $sectionId = DB::table('sections')->where('slug', $section['slug'])->value('id');

            foreach ($section['programs'] as [$name, $slug, $cycle, $description]) {
                DB::table('programs')->updateOrInsert(
                    ['slug' => $slug],
                    [
                        'section_id' => $sectionId,
                        'name' => $name,
                        'cycle' => $cycle,
                        'description' => $description,
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }

        DB::table('sections')
            ->whereIn('slug', ['gestion-commerciale-et-administrative', 'gestion-informatique'])
            ->whereNotIn('slug', $sectionSlugs)
            ->update(['is_active' => false, 'updated_at' => $now]);
    }

    private function institutionServiceBlocks(): void
    {
        $now = now();

        foreach (config('isc_site.institution_service_blocks', []) as [$key, $title, $linkUrl, $order]) {
            DB::table('content_blocks')->updateOrInsert(
                ['key' => $key],
                [
                    'block_group' => 'institution_service',
                    'title' => $title,
                    'subtitle' => null,
                    'body' => null,
                    'image_url' => null,
                    'image_public_id' => null,
                    'image_disk' => null,
                    'image_alt' => null,
                    'link_url' => $linkUrl,
                    'link_label' => null,
                    'icon' => null,
                    'metadata' => null,
                    'sort_order' => $order,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
};
