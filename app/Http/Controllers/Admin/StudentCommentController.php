<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StudentComment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentCommentController extends Controller
{
    public function index(): View
    {
        return view('admin.student-comments.index', [
            'comments' => StudentComment::with('user')->latest()->paginate(20),
        ]);
    }

    public function update(Request $request, StudentComment $comment): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'string', 'max:40'],
            'response' => ['nullable', 'string', 'max:5000'],
        ]);

        $comment->update([
            'status' => $data['status'],
            'response' => $data['response'] ?? null,
            'answered_at' => filled($data['response'] ?? null) ? now() : $comment->answered_at,
        ]);

        return back()->with('status', 'Commentaire etudiant mis a jour.');
    }
}
