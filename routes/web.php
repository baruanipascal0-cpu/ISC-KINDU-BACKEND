<?php

use App\Http\Controllers\Admin\AdmissionController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\ContentBlockController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EnrollmentRegistryController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\GraduationListController;
use App\Http\Controllers\Admin\MessageController;
use App\Http\Controllers\Admin\NewsController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\PublicationController;
use App\Http\Controllers\Admin\ProductionController;
use App\Http\Controllers\Admin\SectionController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\StudentCommentController;
use App\Http\Controllers\FrontendController;
use Illuminate\Support\Facades\Route;

Route::get('/admin/login', [AuthController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AuthController::class, 'login'])->name('admin.login.submit');

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::resource('pages', PageController::class)->except(['show']);
    Route::get('parametres', [SettingController::class, 'index'])->name('settings.index');
    Route::put('parametres', [SettingController::class, 'update'])->name('settings.update');
    Route::resource('blocs-site', ContentBlockController::class)
        ->parameters(['blocs-site' => 'contentBlock'])
        ->names('content-blocks')
        ->except(['show']);
    Route::resource('sections', SectionController::class)->except(['show']);
    Route::resource('actualites', NewsController::class)
        ->parameters(['actualites' => 'news'])
        ->names('news')
        ->except(['show']);
    Route::resource('publications', PublicationController::class)->except(['show']);
    Route::resource('diplomes', GraduationListController::class)
        ->parameters(['diplomes' => 'graduationList'])
        ->names('graduations');
    Route::get('diplomes/{graduationList}/export/csv', [GraduationListController::class, 'exportCsv'])
        ->name('graduations.export');
    Route::resource('evenements', EventController::class)
        ->parameters(['evenements' => 'event'])
        ->names('events')
        ->except(['show']);

    Route::get('inscriptions', [AdmissionController::class, 'index'])->name('admissions.index');
    Route::get('inscriptions/{application}', [AdmissionController::class, 'show'])->name('admissions.show');
    Route::patch('inscriptions/{application}/status', [AdmissionController::class, 'updateStatus'])->name('admissions.status');
    Route::patch('inscriptions/{application}/documents/{document}', [AdmissionController::class, 'updateDocument'])->name('admissions.documents.update');

    Route::get('registre-inscriptions', [EnrollmentRegistryController::class, 'index'])->name('registry.index');
    Route::get('registre-inscriptions/export/csv', [EnrollmentRegistryController::class, 'exportCsv'])->name('registry.export');
    Route::get('registre-inscriptions/{enrollment}', [EnrollmentRegistryController::class, 'show'])->name('registry.show');

    Route::get('messages', [MessageController::class, 'index'])->name('messages.index');
    Route::patch('messages/{message}', [MessageController::class, 'update'])->name('messages.update');
    Route::get('commentaires-etudiants', [StudentCommentController::class, 'index'])->name('student-comments.index');
    Route::patch('commentaires-etudiants/{comment}', [StudentCommentController::class, 'update'])->name('student-comments.update');
    Route::get('production', ProductionController::class)->name('production.index');
});

Route::get('/', FrontendController::class)->name('site.home');
Route::get('/{path}', FrontendController::class)->where('path', '.*')->name('site.file');
