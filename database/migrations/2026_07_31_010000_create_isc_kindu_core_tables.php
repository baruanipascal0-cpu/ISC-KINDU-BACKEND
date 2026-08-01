<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value');
            $table->string('type')->default('text');
            $table->string('group')->default('general')->index();
            $table->timestamps();
        });

        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('menu_items')->nullOnDelete();
            $table->string('location')->default('main')->index();
            $table->string('label');
            $table->string('url');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('body')->nullable();
            $table->string('image_url')->nullable();
            $table->boolean('is_published')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('cycle')->default('Licence');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('news_posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('category')->default('Demo')->index();
            $table->text('excerpt')->nullable();
            $table->longText('body')->nullable();
            $table->string('image_url')->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->boolean('is_published')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('publications', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('type')->default('Communique')->index();
            $table->text('description')->nullable();
            $table->string('file_url')->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->boolean('is_published')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable();
            $table->string('image_url')->nullable();
            $table->boolean('is_published')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('admission_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('section_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('program_id')->nullable()->constrained()->nullOnDelete();
            $table->string('application_number')->unique();
            $table->string('status')->default('draft')->index();
            $table->string('academic_year')->nullable()->index();
            $table->string('level')->nullable();
            $table->string('last_name');
            $table->string('post_name')->nullable();
            $table->string('first_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('birth_place')->nullable();
            $table->string('last_school')->nullable();
            $table->unsignedSmallInteger('diploma_year')->nullable();
            $table->decimal('percentage', 5, 2)->nullable();
            $table->string('guardian_phone')->nullable();
            $table->string('diploma_path')->nullable();
            $table->string('photo_path')->nullable();
            $table->text('comment')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('admission_application_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference')->unique();
            $table->string('label');
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('currency', 8)->default('CDF');
            $table->string('status')->default('pending')->index();
            $table->date('due_date')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('proof_path')->nullable();
            $table->timestamps();
        });

        Schema::create('student_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('admission_application_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('type')->default('document')->index();
            $table->string('file_path')->nullable();
            $table->string('status')->default('available')->index();
            $table->timestamp('issued_at')->nullable();
            $table->timestamps();
        });

        Schema::create('student_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('subject');
            $table->text('message');
            $table->string('status')->default('open')->index();
            $table->text('response')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();
        });

        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('subject')->nullable();
            $table->text('message');
            $table->string('status')->default('new')->index();
            $table->timestamps();
        });

        Schema::create('newsletter_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('status')->default('active')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_subscriptions');
        Schema::dropIfExists('contact_messages');
        Schema::dropIfExists('student_comments');
        Schema::dropIfExists('student_documents');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('admission_applications');
        Schema::dropIfExists('events');
        Schema::dropIfExists('publications');
        Schema::dropIfExists('news_posts');
        Schema::dropIfExists('programs');
        Schema::dropIfExists('sections');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('site_settings');
    }
};
