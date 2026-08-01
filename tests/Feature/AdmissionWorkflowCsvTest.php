<?php

namespace Tests\Feature;

use App\Models\AdmissionApplication;
use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdmissionWorkflowCsvTest extends TestCase
{
    use RefreshDatabase;

    public function test_admission_workflow_creates_student_enrollment_and_csv_registry(): void
    {
        $this->seed();
        Storage::fake('public');

        $section = Section::where('is_active', true)->firstOrFail();
        $program = Program::where('section_id', $section->id)->where('is_active', true)->firstOrFail();

        $this->postJson('/api/auth/register', [
            'last_name' => 'Mukendi',
            'first_name' => 'Jean',
            'email' => 'jean.mukendi@isc-kindu.test',
            'phone' => '+243810000001',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'institution_code' => 'ISC_KINDU',
        ])->assertCreated();

        $login = $this->postJson('/api/auth/login', [
            'login' => 'jean.mukendi@isc-kindu.test',
            'password' => 'secret123',
        ])->assertOk();

        $token = $login->json('data.token');
        $candidate = User::where('email', 'jean.mukendi@isc-kindu.test')->firstOrFail();

        $this->withToken($token)
            ->withHeader('Accept', 'application/json')
            ->post('/api/inscriptions', [
                'academic_year' => '2026-2027',
                'level' => 'L1',
                'section' => $section->name,
                'program' => $program->name,
                'last_name' => 'Mukendi',
                'post_name' => 'Kabamba',
                'first_name' => 'Jean',
                'gender' => 'M',
                'nationality' => 'Congolaise',
                'email' => 'jean.mukendi@isc-kindu.test',
                'phone' => '+243810000001',
                'address' => 'Kindu',
                'birth_place' => 'Kindu',
                'last_school' => 'Institut demo',
                'diploma_year' => 2025,
                'percentage' => 72,
                'guardian_phone' => '+243810000002',
                'comment' => 'Premier envoi.',
                'diploma_file' => UploadedFile::fake()->create('diplome.pdf', 120, 'application/pdf'),
                'photo_file' => UploadedFile::fake()->create('photo.jpg', 80, 'image/jpeg'),
                'institution_code' => 'ISC_KINDU',
            ])->assertCreated()
            ->assertJsonPath('data.status', 'submitted');

        $application = AdmissionApplication::where('email', 'jean.mukendi@isc-kindu.test')->firstOrFail();
        $this->assertDatabaseCount('application_documents', 2);

        $admin = User::where('email', 'admin@isc-kindu.test')->firstOrFail();

        $this->actingAs($admin)->patch(route('admin.admissions.status', $application), [
            'status' => 'under_review',
            'internal_note' => 'Controle commence.',
        ])->assertRedirect();

        $this->actingAs($admin)->patch(route('admin.admissions.status', $application), [
            'status' => 'needs_correction',
            'internal_note' => 'Photo peu lisible.',
            'student_message' => 'Merci de renvoyer une photo plus claire.',
        ])->assertRedirect();

        $this->actingAs($candidate, 'sanctum')
            ->getJson('/api/inscriptions/current')
            ->assertOk()
            ->assertJsonPath('data.application.status', 'needs_correction')
            ->assertJsonPath('data.application.student_message', 'Merci de renvoyer une photo plus claire.');

        $this->actingAs($candidate, 'sanctum')
            ->patchJson('/api/inscriptions/current', [
                'comment' => 'Photo corrigee.',
                'section' => $section->name,
                'program' => $program->name,
                'last_name' => 'Mukendi',
                'first_name' => 'Jean',
                'email' => 'jean.mukendi@isc-kindu.test',
            ])->assertOk()
            ->assertJsonPath('data.status', 'submitted');

        $this->actingAs($admin)->patch(route('admin.admissions.status', $application), [
            'status' => 'approved',
            'internal_note' => 'Dossier conforme.',
            'student_message' => 'Votre admission est approuvee.',
        ])->assertRedirect();

        $student = Student::where('email', 'jean.mukendi@isc-kindu.test')->firstOrFail();
        $enrollment = Enrollment::where('student_id', $student->id)->firstOrFail();

        $this->assertNotEmpty($student->matricule);
        $this->assertNotEmpty($enrollment->enrollment_number);
        Storage::disk('public')->assertExists($enrollment->fiche_path);

        $this->assertDatabaseHas('student_documents', [
            'admission_application_id' => $application->id,
            'type' => 'fiche-inscription',
            'status' => 'available',
        ]);
        $this->assertDatabaseHas('institution_notifications', [
            'user_id' => $application->user_id,
            'type' => 'admission_approved',
        ]);
        $this->assertDatabaseHas('admission_decisions', [
            'admission_application_id' => $application->id,
            'to_status' => 'approved',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'admission.status_changed',
        ]);

        $this->actingAs($admin)
            ->get('/admin/registre-inscriptions?gender=M&search=Mukendi')
            ->assertOk()
            ->assertSee($student->matricule)
            ->assertSee($enrollment->enrollment_number);

        $csv = $this->actingAs($admin)
            ->get('/admin/registre-inscriptions/export/csv?gender=M&search=Mukendi')
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('Numero inscription', $csv);
        $this->assertStringContainsString($student->matricule, $csv);
        $this->assertStringContainsString($enrollment->enrollment_number, $csv);
    }
}
