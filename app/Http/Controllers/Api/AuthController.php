<?php

namespace App\Http\Controllers\Api;

use App\Models\Student;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends ApiController
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'matricule' => ['nullable', 'string', 'max:80'],
            'last_name' => [$request->filled('matricule') ? 'nullable' : 'required', 'string', 'max:120'],
            'first_name' => [$request->filled('matricule') ? 'nullable' : 'required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'institution_code' => ['nullable', 'string', 'max:40'],
        ]);

        if (! empty($data['matricule'])) {
            return $this->registerWithMatricule($data);
        }

        $existingUser = User::query()
            ->where('email', $data['email'])
            ->when($data['phone'] ?? null, fn ($query, string $phone) => $query->orWhere('phone', $phone))
            ->first();

        if ($existingUser) {
            return response()->json([
                'success' => false,
                'message' => 'Ce compte existe deja. Connectez-vous avec votre adresse mail ou votre telephone.',
                'data' => [
                    'user' => $this->userPayload($existingUser),
                    'next_step' => 'login',
                    'has_application' => $existingUser->currentApplication()->exists(),
                ],
            ], 409);
        }

        $user = User::create([
            'name' => trim($data['first_name'].' '.$data['last_name']),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'role' => 'student',
            'status' => 'active',
            'institution_code' => $data['institution_code'] ?? 'ISC_KINDU',
            'password' => $data['password'],
        ]);

        return $this->ok([
            'user' => $this->userPayload($user),
            'token' => $user->createToken('isc-kindu-student')->plainTextToken,
            'next_step' => 'login',
            'has_application' => false,
        ], 'Compte etudiant cree.', 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'login' => ['nullable', 'required_without:matricule', 'string', 'max:190'],
            'matricule' => ['nullable', 'required_without:login', 'string', 'max:80'],
            'password' => ['required', 'string'],
        ]);

        $login = $data['login'] ?? $data['matricule'];
        $user = $this->userForLogin($login);

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['Identifiants incorrects.'],
            ]);
        }

        if ($user->status !== 'active') {
            throw ValidationException::withMessages([
                'login' => ['Ce compte n est pas actif.'],
            ]);
        }

        $application = $user->currentApplication()->first();
        $nextStep = 'admission_form';

        if ($application && ! in_array($application->status, ['draft', 'needs_correction'], true)) {
            $nextStep = 'student_wallet';
        }

        return $this->ok([
            'user' => $this->userPayload($user),
            'token' => $user->createToken('isc-kindu-'.$user->role)->plainTextToken,
            'has_application' => (bool) $application,
            'next_step' => $nextStep,
            'application' => $application ? [
                'id' => $application->id,
                'application_number' => $application->application_number,
                'status' => $application->status,
            ] : null,
        ], 'Connexion reussie.');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->ok([
            'user' => $this->userPayload($request->user()),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return $this->ok([], 'Deconnexion reussie.');
    }

    private function registerWithMatricule(array $data): JsonResponse
    {
        $student = Student::query()
            ->with('user')
            ->where('matricule', $data['matricule'])
            ->first();

        if (! $student) {
            throw ValidationException::withMessages([
                'matricule' => ['Matricule introuvable. Verifiez le matricule donne par l apparitorat apres validation.'],
            ]);
        }

        $email = Str::lower($data['email']);
        $phone = $data['phone'] ?? null;
        $linkedUser = $student->user;
        $existingUser = User::query()
            ->where('email', $email)
            ->when($phone, fn ($query, string $phone) => $query->orWhere('phone', $phone))
            ->first();

        if ($existingUser && $linkedUser && $existingUser->id !== $linkedUser->id) {
            throw ValidationException::withMessages([
                'email' => ['Cet email ou telephone est deja utilise par un autre compte.'],
            ]);
        }

        if ($existingUser && ! $linkedUser && $existingUser->email !== $email) {
            throw ValidationException::withMessages([
                'phone' => ['Ce telephone est deja utilise par un autre compte.'],
            ]);
        }

        if ($existingUser?->isAdmin() || $linkedUser?->isAdmin()) {
            throw ValidationException::withMessages([
                'email' => ['Cette adresse email appartient a un compte administrateur.'],
            ]);
        }

        $user = $linkedUser ?: $existingUser;

        $payload = [
            'name' => $student->full_name ?: trim(($data['first_name'] ?? '').' '.($data['last_name'] ?? '')),
            'first_name' => $student->first_name ?: ($data['first_name'] ?? null),
            'last_name' => $student->last_name ?: ($data['last_name'] ?? null),
            'email' => $email,
            'phone' => $phone ?: $student->phone,
            'role' => 'student',
            'status' => 'active',
            'institution_code' => $data['institution_code'] ?? 'ISC_KINDU',
            'password' => $data['password'],
        ];

        if ($user) {
            $user->forceFill(array_filter($payload, fn ($value) => $value !== null && $value !== ''))->save();
        } else {
            $user = User::create($payload);
        }

        $student->forceFill([
            'user_id' => $user->id,
            'email' => $email,
            'phone' => $phone ?: $student->phone,
        ])->save();

        return $this->ok([
            'user' => $this->userPayload($user),
            'token' => $user->createToken('isc-kindu-student')->plainTextToken,
            'next_step' => 'student_wallet',
            'has_application' => $user->currentApplication()->exists(),
        ], 'Compte etudiant active.', 201);
    }

    private function userForLogin(string $login): ?User
    {
        $user = User::query()
            ->where('email', $login)
            ->orWhere('phone', $login)
            ->first();

        if ($user) {
            return $user;
        }

        return Student::query()
            ->with('user')
            ->where('matricule', $login)
            ->first()
            ?->user;
    }

    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role,
            'status' => $user->status,
            'institution_code' => $user->institution_code,
        ];
    }
}
