<?php

namespace App\Http\Controllers\Api;

use App\Models\ContactMessage;
use App\Models\NewsletterSubscription;
use App\Models\Publication;
use App\Support\UniqueSlug;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ContactController extends ApiController
{
    public function message(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'email' => ['nullable', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'subject' => ['nullable', 'string', 'max:160'],
            'message' => ['required', 'string', 'max:3000'],
        ]);

        $message = ContactMessage::create($data + ['status' => 'new']);

        return $this->ok($message, 'Message recu.', 201);
    }

    public function opportunity(Request $request): JsonResponse
    {
        $data = $request->validate([
            '_form' => ['nullable', 'string', 'max:80'],
            'title' => ['required', 'string', 'max:190'],
            'organization' => ['required', 'string', 'max:190'],
            'type' => ['nullable', 'string', 'max:120'],
            'location' => ['nullable', 'string', 'max:190'],
            'deadline' => ['nullable', 'date'],
            'apply_contact' => ['nullable', 'string', 'max:190'],
            'summary' => ['nullable', 'string', 'max:3000'],
        ]);

        $type = $this->opportunityType($data);
        $description = collect([
            'Organisation: '.($data['organization'] ?? 'A completer'),
            isset($data['location']) ? 'Lieu: '.$data['location'] : null,
            isset($data['deadline']) ? 'Date limite: '.$data['deadline'] : null,
            isset($data['apply_contact']) ? 'Contact de candidature: '.$data['apply_contact'] : null,
            '',
            $data['summary'] ?? null,
        ])->filter(fn (?string $line): bool => $line !== null)->implode("\n");

        $publication = Publication::create([
            'title' => $data['title'],
            'slug' => UniqueSlug::forModel(Publication::class, $data['title']),
            'type' => $type,
            'description' => $description,
            'published_at' => null,
            'is_published' => false,
        ]);

        return $this->ok($publication, 'Proposition recue. Elle sera verifiee avant publication.', 201);
    }

    public function newsletter(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:190'],
        ]);

        $subscription = NewsletterSubscription::updateOrCreate(
            ['email' => $data['email']],
            ['status' => 'active']
        );

        return $this->ok($subscription, 'Abonnement enregistre.', 201);
    }

    private function opportunityType(array $data): string
    {
        $form = Str::lower((string) ($data['_form'] ?? ''));
        $type = Str::lower((string) ($data['type'] ?? ''));

        if (Str::contains($form, 'emploi') || Str::contains($type, ['emploi', 'job'])) {
            return 'Emploi';
        }

        if (Str::contains($type, 'stage')) {
            return 'Stage';
        }

        if (Str::contains($type, 'offre')) {
            return 'Offre';
        }

        return 'Opportunite';
    }
}
