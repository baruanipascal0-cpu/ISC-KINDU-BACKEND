<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['news_posts', 'events', 'pages', 'content_blocks'] as $table) {
            $this->addImageMetadata($table);
        }

        if (Schema::hasTable('publications') && ! Schema::hasColumn('publications', 'image_url')) {
            Schema::table('publications', function (Blueprint $table) {
                $table->string('image_url')->nullable();
            });
        }

        $this->addImageMetadata('publications');
    }

    public function down(): void
    {
        foreach (['news_posts', 'events', 'pages', 'content_blocks', 'publications'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $columns = array_values(array_filter([
                Schema::hasColumn($table, 'image_public_id') ? 'image_public_id' : null,
                Schema::hasColumn($table, 'image_disk') ? 'image_disk' : null,
                Schema::hasColumn($table, 'image_alt') ? 'image_alt' : null,
                $table === 'publications' && Schema::hasColumn($table, 'image_url') ? 'image_url' : null,
            ]));

            if ($columns !== []) {
                Schema::table($table, function (Blueprint $table) use ($columns) {
                    $table->dropColumn($columns);
                });
            }
        }
    }

    private function addImageMetadata(string $tableName): void
    {
        if (! Schema::hasTable($tableName)) {
            return;
        }

        if (! Schema::hasColumn($tableName, 'image_public_id')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('image_public_id')->nullable();
            });
        }

        if (! Schema::hasColumn($tableName, 'image_disk')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('image_disk')->nullable();
            });
        }

        if (! Schema::hasColumn($tableName, 'image_alt')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('image_alt')->nullable();
            });
        }
    }
};
