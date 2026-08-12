<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IscKinduApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_reports_database_and_admin_status(): void
    {
        $this->seed();

        $this->getJson('/api/health')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'ok')
            ->assertJsonPath('data.database.status', 'ok')
            ->assertJsonPath('data.database.admin_exists', true);
    }

    public function test_public_api_returns_isc_demo_content(): void
    {
        $this->seed();

        $this->getJson('/api/site/settings')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['institution.name' => 'ISC KINDU']);

        $this->getJson('/api/sections')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Sciences et Technologies'])
            ->assertJsonFragment(['name' => 'Systemes Informatiques']);

        $this->getJson('/api/home/cards')
            ->assertOk()
            ->assertJsonFragment(['title' => 'Inscriptions']);

        $this->getJson('/api/site/blocks/admission_step')
            ->assertOk()
            ->assertJsonFragment(['key' => 'admission_step.form']);
    }

    public function test_student_can_register_login_submit_application_and_open_dashboard(): void
    {
        $this->seed();

        $this->postJson('/api/auth/register', [
            'last_name' => 'Candidat',
            'first_name' => 'Demo',
            'email' => 'candidat.demo@isc-kindu.test',
            'phone' => '+243000100001',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'institution_code' => 'ISC_KINDU',
        ])->assertCreated()
            ->assertJsonPath('success', true);

        $login = $this->postJson('/api/auth/login', [
            'login' => 'candidat.demo@isc-kindu.test',
            'password' => 'secret123',
        ])->assertOk()
            ->assertJsonPath('success', true);

        $token = $login->json('data.token');
        $this->assertNotEmpty($token);

        $this->withToken($token)->postJson('/api/inscriptions', [
            'academic_year' => '2026-2027',
            'level' => 'L1',
            'section' => 'Sciences et Technologies',
            'program' => 'Systemes Informatiques',
            'last_name' => 'Candidat',
            'post_name' => 'Postnom',
            'first_name' => 'Demo',
            'email' => 'candidat.demo@isc-kindu.test',
            'phone' => '+243000100001',
            'address' => 'Adresse demo, Kindu',
            'birth_place' => 'Kindu',
            'last_school' => 'Ecole demo',
            'diploma_year' => 2025,
            'percentage' => 70,
            'guardian_phone' => '+243000100002',
            'comment' => 'Inscription demo.',
            'institution_code' => 'ISC_KINDU',
        ])->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'submitted');

        $this->withToken($token)->postJson('/api/inscriptions', [
            'section' => 'Sciences et Technologies',
            'program' => 'Systemes Informatiques',
            'last_name' => 'Candidat',
            'first_name' => 'Demo',
            'email' => 'candidat.demo@isc-kindu.test',
        ])->assertStatus(409)
            ->assertJsonPath('data.next_step', 'student_wallet');

        $this->assertDatabaseHas('admission_applications', [
            'email' => 'candidat.demo@isc-kindu.test',
            'status' => 'submitted',
        ]);

        $this->withToken($token)->getJson('/api/student/dashboard')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.student.email', 'candidat.demo@isc-kindu.test');
    }

    public function test_existing_student_account_is_sent_to_login_instead_of_hard_error(): void
    {
        $this->seed();

        $this->postJson('/api/auth/register', [
            'last_name' => 'Candidat',
            'first_name' => 'Existant',
            'email' => 'etudiant@isc-kindu.test',
            'phone' => '+243000000003',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'institution_code' => 'ISC_KINDU',
        ])->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('data.next_step', 'login')
            ->assertJsonPath('data.user.email', 'etudiant@isc-kindu.test');
    }

    public function test_validated_student_can_activate_account_and_login_with_matricule(): void
    {
        $this->seed();

        $user = User::create([
            'name' => 'Alice Kalume',
            'first_name' => 'Alice',
            'last_name' => 'Kalume',
            'email' => 'alice.kalume.account@isc-kindu.test',
            'phone' => '+243810009991',
            'role' => 'student',
            'status' => 'active',
            'institution_code' => 'ISC_KINDU',
            'password' => 'ancien-secret',
        ]);

        Student::create([
            'user_id' => $user->id,
            'matricule' => 'ISC-2026-0099',
            'last_name' => 'Kalume',
            'post_name' => 'Bora',
            'first_name' => 'Alice',
            'gender' => 'F',
            'email' => $user->email,
            'phone' => $user->phone,
            'status' => 'active',
        ]);

        $this->postJson('/api/auth/register', [
            'matricule' => 'ISC-2026-0099',
            'email' => 'alice.kalume.account@isc-kindu.test',
            'phone' => '+243810009991',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'institution_code' => 'ISC_KINDU',
        ])->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.next_step', 'student_wallet');

        $login = $this->postJson('/api/auth/login', [
            'matricule' => 'ISC-2026-0099',
            'password' => 'secret123',
        ])->assertOk()
            ->assertJsonPath('success', true);

        $this->withToken($login->json('data.token'))
            ->getJson('/api/student/dashboard')
            ->assertOk()
            ->assertJsonPath('data.student.matricule', 'ISC-2026-0099')
            ->assertJsonPath('data.student.email', 'alice.kalume.account@isc-kindu.test');
    }
}
