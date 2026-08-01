<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->string('status')->default('active')->index();
            $table->boolean('is_current')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('levels', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('cycle')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('level_id')->nullable()->constrained('levels')->nullOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::table('admission_applications', function (Blueprint $table) {
            if (! Schema::hasColumn('admission_applications', 'academic_year_id')) {
                $table->foreignId('academic_year_id')->nullable()->after('program_id')->constrained('academic_years')->nullOnDelete();
            }
            if (! Schema::hasColumn('admission_applications', 'level_id')) {
                $table->foreignId('level_id')->nullable()->after('academic_year_id')->constrained('levels')->nullOnDelete();
            }
            if (! Schema::hasColumn('admission_applications', 'promotion_id')) {
                $table->foreignId('promotion_id')->nullable()->after('level_id')->constrained('promotions')->nullOnDelete();
            }
            if (! Schema::hasColumn('admission_applications', 'gender')) {
                $table->string('gender', 20)->nullable()->after('first_name')->index();
            }
            if (! Schema::hasColumn('admission_applications', 'nationality')) {
                $table->string('nationality')->nullable()->after('gender');
            }
            if (! Schema::hasColumn('admission_applications', 'student_message')) {
                $table->text('student_message')->nullable()->after('review_note');
            }
            if (! Schema::hasColumn('admission_applications', 'internal_note')) {
                $table->text('internal_note')->nullable()->after('student_message');
            }
            if (! Schema::hasColumn('admission_applications', 'reviewed_by')) {
                $table->foreignId('reviewed_by')->nullable()->after('internal_note')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('admission_applications', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('reviewed_by')->index();
            }
            if (! Schema::hasColumn('admission_applications', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('approved_at')->index();
            }
            if (! Schema::hasColumn('admission_applications', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('rejected_at')->index();
            }
        });

        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('admission_application_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('section_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('program_id')->nullable()->constrained()->nullOnDelete();
            $table->string('matricule')->unique();
            $table->string('last_name');
            $table->string('post_name')->nullable();
            $table->string('first_name');
            $table->string('gender', 20)->nullable()->index();
            $table->date('birth_date')->nullable();
            $table->string('birth_place')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamp('admitted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('admission_application_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->restrictOnDelete();
            $table->foreignId('section_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('program_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('level_id')->nullable()->constrained('levels')->nullOnDelete();
            $table->foreignId('promotion_id')->nullable()->constrained('promotions')->nullOnDelete();
            $table->string('enrollment_number')->unique();
            $table->string('type')->default('nouvelle_inscription')->index();
            $table->string('status')->default('active')->index();
            $table->date('enrolled_on')->nullable()->index();
            $table->string('fiche_path')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_required')->default(true)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('application_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_type_id')->nullable()->constrained('document_types')->nullOnDelete();
            $table->string('name');
            $table->string('file_path')->nullable();
            $table->string('status')->default('submitted')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamps();
        });

        Schema::create('document_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('decision')->index();
            $table->text('internal_note')->nullable();
            $table->text('student_message')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
        });

        Schema::create('admission_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status')->index();
            $table->text('internal_note')->nullable();
            $table->text('student_message')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
        });

        Schema::create('institution_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('admission_application_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->default('info')->index();
            $table->string('channel')->default('database')->index();
            $table->string('title');
            $table->text('message');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'student_id')) {
                $table->foreignId('student_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('payments', 'enrollment_id')) {
                $table->foreignId('enrollment_id')->nullable()->after('admission_application_id')->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('payments', 'paid_amount')) {
                $table->decimal('paid_amount', 12, 2)->default(0)->after('amount');
            }
        });

        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('enrollment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('receipt_number')->unique();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('currency', 8)->default('CDF');
            $table->timestamp('issued_at')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamps();
        });

        Schema::create('graduation_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->foreignId('section_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('program_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('promotion_id')->nullable()->constrained('promotions')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('cycle')->nullable();
            $table->date('decision_date')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->string('status')->default('draft')->index();
            $table->timestamps();
        });

        Schema::create('graduates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('graduation_list_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->string('matricule')->nullable()->index();
            $table->string('last_name');
            $table->string('post_name')->nullable();
            $table->string('first_name');
            $table->string('gender', 20)->nullable();
            $table->decimal('percentage', 5, 2)->nullable();
            $table->string('mention')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action')->index();
            $table->string('auditable_type')->nullable()->index();
            $table->unsignedBigInteger('auditable_id')->nullable()->index();
            $table->text('description')->nullable();
            $table->json('before_values')->nullable();
            $table->json('after_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });

        Schema::create('media_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_files');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('graduates');
        Schema::dropIfExists('graduation_lists');
        Schema::dropIfExists('receipts');

        Schema::table('payments', function (Blueprint $table) {
            foreach (['student_id', 'enrollment_id', 'paid_amount'] as $column) {
                if (Schema::hasColumn('payments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('institution_notifications');
        Schema::dropIfExists('admission_decisions');
        Schema::dropIfExists('document_reviews');
        Schema::dropIfExists('application_documents');
        Schema::dropIfExists('document_types');
        Schema::dropIfExists('enrollments');
        Schema::dropIfExists('students');

        Schema::table('admission_applications', function (Blueprint $table) {
            foreach ([
                'academic_year_id',
                'level_id',
                'promotion_id',
                'gender',
                'nationality',
                'student_message',
                'internal_note',
                'reviewed_by',
                'approved_at',
                'rejected_at',
                'cancelled_at',
            ] as $column) {
                if (Schema::hasColumn('admission_applications', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('promotions');
        Schema::dropIfExists('levels');
        Schema::dropIfExists('academic_years');
    }
};
