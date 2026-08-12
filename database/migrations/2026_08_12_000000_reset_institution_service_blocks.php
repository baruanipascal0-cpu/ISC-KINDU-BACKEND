<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach ($this->serviceBlocks() as $block) {
            DB::table('content_blocks')->updateOrInsert(
                ['key' => $block['key']],
                [
                    'block_group' => 'home_service',
                    'title' => null,
                    'subtitle' => null,
                    'body' => null,
                    'image_url' => null,
                    'image_public_id' => null,
                    'image_disk' => null,
                    'image_alt' => null,
                    'link_url' => null,
                    'link_label' => null,
                    'icon' => null,
                    'metadata' => null,
                    'sort_order' => $block['sort_order'],
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
    }

    private function serviceBlocks(): array
    {
        return [
            ['key' => 'home_service.academique', 'sort_order' => 1],
            ['key' => 'home_service.administration', 'sort_order' => 2],
            ['key' => 'home_service.recherche', 'sort_order' => 3],
            ['key' => 'home_service.orientation', 'sort_order' => 4],
        ];
    }
};
