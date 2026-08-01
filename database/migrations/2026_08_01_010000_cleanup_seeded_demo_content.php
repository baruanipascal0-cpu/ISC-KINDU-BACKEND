<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->removeSeededPosts();
        $this->removeDemoStudentWallet();
        $this->clearDemoText();
    }

    public function down(): void
    {
    }

    private function removeSeededPosts(): void
    {
        DB::table('news_posts')
            ->where('slug', 'like', 'actualite-demo-%')
            ->orWhere('category', 'Demo')
            ->delete();

        DB::table('publications')
            ->where('slug', 'like', 'publication-demo-%')
            ->orWhereIn('slug', ['diplome-demo', 'ressource-demo', 'memoriam-demo', 'alumni-demo'])
            ->delete();

        DB::table('events')
            ->where('slug', 'like', 'evenement-demo-%')
            ->delete();

        DB::table('site_settings')
            ->where('key', 'admissions.demo_notice')
            ->delete();
    }

    private function removeDemoStudentWallet(): void
    {
        $applicationIds = DB::table('admission_applications')
            ->where('application_number', 'ISC-DEMO-0001')
            ->pluck('id');

        if ($applicationIds->isNotEmpty()) {
            DB::table('payments')
                ->whereIn('admission_application_id', $applicationIds)
                ->orWhere('reference', 'like', 'PAY-ISC-DEMO-%')
                ->delete();

            DB::table('student_documents')
                ->whereIn('admission_application_id', $applicationIds)
                ->delete();

            DB::table('admission_applications')
                ->whereIn('id', $applicationIds)
                ->delete();
        }

        $demoUserIds = DB::table('users')
            ->where('email', 'etudiant@isc-kindu.test')
            ->pluck('id');

        if ($demoUserIds->isNotEmpty()) {
            DB::table('student_comments')
                ->whereIn('user_id', $demoUserIds)
                ->where(function ($query): void {
                    $query->where('subject', 'like', '%demo%')
                        ->orWhere('message', 'like', '%demonstration%');
                })
                ->delete();

            DB::table('personal_access_tokens')
                ->where('tokenable_type', 'App\\Models\\User')
                ->whereIn('tokenable_id', $demoUserIds)
                ->delete();

            DB::table('users')
                ->whereIn('id', $demoUserIds)
                ->delete();
        }
    }

    private function clearDemoText(): void
    {
        $this->replaceText('pages', ['excerpt', 'body'], 'Contenu a completer depuis l espace administrateur.');
        $this->replaceText('content_blocks', ['subtitle', 'body'], 'Contenu a completer depuis l espace administrateur.');
        $this->replaceText('sections', ['description'], 'Description a completer par les informations officielles.');
        $this->replaceText('programs', ['description'], 'Programme a completer par les informations officielles.');
    }

    private function replaceText(string $table, array $columns, string $replacement): void
    {
        foreach ($columns as $column) {
            DB::table($table)
                ->where(function ($query) use ($column): void {
                    $query->where($column, 'like', '%demo%')
                        ->orWhere($column, 'like', '%Demo%')
                        ->orWhere($column, 'like', '%demonstration%');
                })
                ->update([$column => $replacement]);
        }
    }
};
