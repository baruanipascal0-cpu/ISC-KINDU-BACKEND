<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MessageController extends Controller
{
    public function index(): View
    {
        return view('admin.messages.index', [
            'messages' => ContactMessage::latest()->paginate(20),
        ]);
    }

    public function update(Request $request, ContactMessage $message): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'string', 'max:40'],
            'response' => ['nullable', 'string', 'max:5000'],
        ]);

        $message->update([
            'status' => $data['status'],
            'response' => $data['response'] ?? null,
            'answered_at' => filled($data['response'] ?? null) ? now() : $message->answered_at,
        ]);

        return back()->with('status', 'Message mis a jour.');
    }
}
