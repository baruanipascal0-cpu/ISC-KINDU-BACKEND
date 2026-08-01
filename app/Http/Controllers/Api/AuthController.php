<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends ApiController
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'last_name' => ['required', 'string', 'max:120'],
            'first_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'institution_code' => ['nullable', 'string', 'max:40'],
        ]);

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
            'login' => ['required', 'string', 'max:190'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()
            ->where('email', $data['login'])
            ->orWhere('phone', $data['login'])
            ->first();

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
