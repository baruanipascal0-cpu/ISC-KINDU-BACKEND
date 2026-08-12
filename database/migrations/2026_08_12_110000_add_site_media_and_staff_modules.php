<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media_files', function (Blueprint $table) {
            if (! Schema::hasColumn('media_files', 'slug')) {
                $table->string('slug')->nullable()->unique()->after('name');
            }
            if (! Schema::hasColumn('media_files', 'collection')) {
                $table->string('collection')->default('gallery')->index()->after('slug');
            }
            if (! Schema::hasColumn('media_files', 'caption')) {
                $table->text('caption')->nullable()->after('collection');
            }
            if (! Schema::hasColumn('media_files', 'alt_text')) {
                $table->string('alt_text')->nullable()->after('caption');
            }
            if (! Schema::hasColumn('media_files', 'disk')) {
                $table->string('disk')->nullable()->after('path');
            }
            if (! Schema::hasColumn('media_files', 'public_id')) {
                $table->string('public_id')->nullable()->after('disk');
            }
            if (! Schema::hasColumn('media_files', 'is_published')) {
                $table->boolean('is_published')->default(true)->index()->after('size');
            }
            if (! Schema::hasColumn('media_files', 'published_at')) {
                $table->timestamp('published_at')->nullable()->index()->after('is_published');
            }
            if (! Schema::hasColumn('media_files', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('published_at');
            }
            if (! Schema::hasColumn('media_files', 'metadata')) {
                $table->json('metadata')->nullable()->after('sort_order');
            }
        });

        DB::table('media_files')
            ->whereNull('slug')
            ->orderBy('id')
            ->get(['id', 'name'])
            ->each(function (object $media): void {
                $base = Str::slug((string) $media->name) ?: 'media-'.$media->id;
                $slug = $base;
                $suffix = 2;

                while (DB::table('media_files')->where('slug', $slug)->where('id', '!=', $media->id)->exists()) {
                    $slug = $base.'-'.$suffix++;
                }

                DB::table('media_files')->where('id', $media->id)->update(['slug' => $slug]);
            });

        Schema::create('staff_members', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('title')->nullable();
            $table->string('role')->default('enseignant')->index();
            $table->string('department')->nullable()->index();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('biography')->nullable();
            $table->string('image_url')->nullable();
            $table->string('image_public_id')->nullable();
            $table->string('image_disk')->nullable();
            $table->string('image_alt')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_members');

        Schema::table('media_files', function (Blueprint $table) {
            foreach ([
                'slug',
                'collection',
                'caption',
                'alt_text',
                'disk',
                'public_id',
                'is_published',
                'published_at',
                'sort_order',
                'metadata',
            ] as $column) {
                if (Schema::hasColumn('media_files', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
