<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdmissionApplication;
use App\Models\ContactMessage;
use App\Models\ContentBlock;
use App\Models\Enrollment;
use App\Models\Event;
use App\Models\GraduationList;
use App\Models\MediaFile;
use App\Models\NewsPost;
use App\Models\Page;
use App\Models\Program;
use App\Models\Publication;
use App\Models\Section;
use App\Models\StaffMember;
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
                'Pages du site' => Page::count(),
                'Blocs ISC' => ContentBlock::count(),
                'Actualites' => NewsPost::count(),
                'Publications' => Publication::count(),
                'Medias' => MediaFile::count(),
                'Enseignants' => StaffMember::count(),
                'Filieres' => Program::where('is_active', true)->count(),
                'Inscriptions' => AdmissionApplication::count(),
                'Messages nouveaux' => ContactMessage::where('status', 'new')->count(),
            ],
            'siteModules' => config('isc_site.site_modules', []),
            'latestApplications' => AdmissionApplication::with(['user', 'section', 'program'])->latest()->take(6)->get(),
            'latestNews' => NewsPost::latest()->take(5)->get(),
            'messages' => ContactMessage::latest()->take(5)->get(),
        ]);
    }
}
