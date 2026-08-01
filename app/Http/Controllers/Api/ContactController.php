<?php

namespace App\Http\Controllers\Api;

use App\Models\ContactMessage;
use App\Models\NewsletterSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
}
