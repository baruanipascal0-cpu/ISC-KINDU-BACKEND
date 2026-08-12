<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ContactMessage;
use App\Models\Enrollment;
use App\Models\GraduationList;
use App\Models\Program;
use App\Models\Promotion;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AdminGraduationMessagingRegistryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_publish_graduation_list_and_public_api_displays_graduates(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@isc-kindu.test')->firstOrFail();
        $year = AcademicYear::where('is_current', true)->firstOrFail();
        $section = Section::where('is_active', true)->firstOrFail();
        $program = Program::where('section_id', $section->id)->firstOrFail();
        $promotion = Promotion::orderBy('sort_order')->firstOrFail();

        $this->actingAs($admin)->post(route('admin.graduations.store'), [
            'title' => 'Liste officielle L1 Informatique',
            'academic_year_id' => $year->id,
            'section_id' => $section->id,
            'program_id' => $program->id,
            'promotion_id' => $promotion->id,
            'cycle' => 'Licence',
            'published_at' => now()->format('Y-m-d H:i:s'),
            'status' => 'published',
            'graduates_text' => "ISC-2026-0001;KASONGO;MUTOMBO;Jean;M;75;Distinction\nISC-2026-0002;KALALA;MBUYI;Marie;F;68;Satisfaction",
        ])->assertRedirect(route('admin.graduations.index'));

        $list = GraduationList::where('slug', 'liste-officielle-l1-informatique')->firstOrFail();

        $this->getJson('/api/graduation-lists')
            ->assertOk()
            ->assertJsonFragment(['title' => 'Liste officielle L1 Informatique'])
            ->assertJsonFragment(['graduates_count' => 2]);

        $this->getJson('/api/graduation-lists/'.$list->slug)
            ->assertOk()
            ->assertJsonFragment(['last_name' => 'KASONGO'])
            ->assertJsonFragment(['first_name' => 'Marie']);
    }

    public function test_admin_can_import_graduates_from_csv_file(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@isc-kindu.test')->firstOrFail();
        $year = AcademicYear::where('is_current', true)->firstOrFail();
        $section = Section::where('is_active', true)->firstOrFail();
        $program = Program::where('section_id', $section->id)->firstOrFail();
        $promotion = Promotion::orderBy('sort_order')->firstOrFail();

        $file = UploadedFile::fake()->createWithContent(
            'diplomes.csv',
            "Matricule;Nom;Postnom;Prenom;Sexe;Pourcentage;Mention\nISC-2026-0100;MUKENDI;KABASELE;Anne;F;78;Distinction"
        );

        $this->actingAs($admin)->post(route('admin.graduations.store'), [
            'title' => 'Liste officielle importee',
            'academic_year_id' => $year->id,
            'section_id' => $section->id,
            'program_id' => $program->id,
            'promotion_id' => $promotion->id,
            'cycle' => 'Licence',
            'published_at' => now()->format('Y-m-d H:i:s'),
            'status' => 'published',
            'graduates_file' => $file,
        ])->assertRedirect(route('admin.graduations.index'));

        $this->assertDatabaseHas('graduates', [
            'matricule' => 'ISC-2026-0100',
            'last_name' => 'MUKENDI',
            'first_name' => 'Anne',
        ]);

        $this->getJson('/api/graduation-lists/liste-officielle-importee')
            ->assertOk()
            ->assertJsonFragment(['graduates_count' => 1]);
    }

    public function test_admin_can_answer_contact_message(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@isc-kindu.test')->firstOrFail();
        $message = ContactMessage::create([
            'name' => 'Visiteur',
            'email' => 'visiteur@example.test',
            'subject' => 'Demande',
            'message' => 'Je souhaite avoir une information.',
            'status' => 'new',
        ]);

        $this->actingAs($admin)->patch(route('admin.messages.update', $message), [
            'status' => 'answered',
            'response' => 'Merci. Votre message est bien recu.',
        ])->assertRedirect();

        $this->assertDatabaseHas('contact_messages', [
            'id' => $message->id,
            'status' => 'answered',
            'response' => 'Merci. Votre message est bien recu.',
        ]);
    }

    public function test_student_documents_api_exposes_public_file_url(): void
    {
        $this->seed();

        $studentUser = User::where('email', 'etudiant@isc-kindu.test')->firstOrFail();

        StudentDocument::create([
            'user_id' => $studentUser->id,
            'name' => 'Fiche d inscription',
            'type' => 'fiche-inscription',
            'file_path' => 'enrollments/fiche-test.html',
            'status' => 'available',
            'issued_at' => now(),
        ]);

        $this->actingAs($studentUser, 'sanctum')
            ->getJson('/api/student/documents')
            ->assertOk()
            ->assertJsonFragment(['file_url' => asset('storage/enrollments/fiche-test.html')]);
    }

    public function test_registry_shows_all_enrollments_ordered_by_promotion_and_program(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@isc-kindu.test')->firstOrFail();
        $year = AcademicYear::where('is_current', true)->firstOrFail();
        $section = Section::where('is_active', true)->firstOrFail();
        $programs = Program::where('section_id', $section->id)->orderBy('name')->take(2)->get();
        $promotion = Promotion::orderBy('sort_order')->firstOrFail();

        foreach ([
            ['ISC-2026-0101', 'INS-20262027-0101', 'KABONGO', $programs[0]],
            ['ISC-2026-0102', 'INS-20262027-0102', 'MULUMBA', $programs[0]],
            ['ISC-2026-0103', 'INS-20262027-0103', 'TSHIBANGU', $programs[1] ?? $programs[0]],
        ] as [$matricule, $number, $name, $program]) {
            $student = Student::create([
                'section_id' => $section->id,
                'program_id' => $program->id,
                'matricule' => $matricule,
                'last_name' => $name,
                'first_name' => 'Test',
                'gender' => 'M',
                'status' => 'active',
            ]);

            Enrollment::create([
                'student_id' => $student->id,
                'academic_year_id' => $year->id,
                'section_id' => $section->id,
                'program_id' => $program->id,
                'promotion_id' => $promotion->id,
                'enrollment_number' => $number,
                'type' => 'nouvelle_inscription',
                'status' => 'active',
                'enrolled_on' => now()->toDateString(),
            ]);
        }

        $this->actingAs($admin)
            ->get('/admin/registre-inscriptions?promotion_id='.$promotion->id)
            ->assertOk()
            ->assertSee("LISTE D'INSCRIPTION", false)
            ->assertSee('KABONGO')
            ->assertSee('MULUMBA')
            ->assertSee('TSHIBANGU');
    }
}
