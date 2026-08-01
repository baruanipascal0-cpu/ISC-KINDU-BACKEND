<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdmissionApplication;
use App\Models\ContactMessage;
use App\Models\ContentBlock;
use App\Models\Enrollment;
use App\Models\Event;
use App\Models\NewsPost;
use App\Models\Page;
use App\Models\Publication;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentComment;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'stats' => [
                'pages' => Page::count(),
                'blocs_site' => ContentBlock::count(),
                'actualites' => NewsPost::count(),
                'publications' => Publication::count(),
                'evenements' => Event::count(),
                'sections' => Section::count(),
                'inscriptions' => AdmissionApplication::count(),
                'registre' => Enrollment::count(),
                'etudiants' => Student::count(),
                'messages' => ContactMessage::where('status', 'new')->count(),
                'commentaires_etudiants' => StudentComment::where('status', 'open')->count(),
            ],
            'latestApplications' => AdmissionApplication::with(['user', 'section', 'program'])->latest()->take(6)->get(),
            'latestNews' => NewsPost::latest()->take(5)->get(),
            'messages' => ContactMessage::latest()->take(5)->get(),
        ]);
    }
}
