<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AdmissionController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\ContentController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\StudentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/health', HealthController::class);

Route::get('/site/settings', [ContentController::class, 'settings']);
Route::get('/site/menus', [ContentController::class, 'menus']);
Route::get('/site/blocks', [ContentController::class, 'blocks']);
Route::get('/site/blocks/{group}', [ContentController::class, 'blocks']);
Route::get('/media', [ContentController::class, 'media']);

Route::get('/home/slides', [ContentController::class, 'homeSlides']);
Route::get('/home/cards', [ContentController::class, 'homeCards']);
Route::get('/home/statistics', [ContentController::class, 'homeStatistics']);

Route::get('/pages', [ContentController::class, 'pages']);
Route::get('/pages/{slug}', [ContentController::class, 'page']);

Route::get('/sections', [ContentController::class, 'sections']);
Route::get('/sections/{slug}', [ContentController::class, 'section']);
Route::get('/programs', [ContentController::class, 'programs']);

Route::get('/news/categories', [ContentController::class, 'newsCategories']);
Route::get('/news', [ContentController::class, 'news']);
Route::get('/news/{slug}', [ContentController::class, 'newsShow']);

Route::get('/publications', [ContentController::class, 'publications']);
Route::get('/publications/{slug}', [ContentController::class, 'publicationShow']);

Route::get('/graduation-lists', [ContentController::class, 'graduationLists']);
Route::get('/graduation-lists/{slug}', [ContentController::class, 'graduationListShow']);

Route::get('/events', [ContentController::class, 'events']);
Route::get('/events/{slug}', [ContentController::class, 'eventShow']);

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::post('/contact/messages', [ContactController::class, 'message']);
Route::post('/newsletter', [ContactController::class, 'newsletter']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::post('/inscriptions', [AdmissionController::class, 'store']);
    Route::get('/inscriptions/current', [AdmissionController::class, 'current']);
    Route::match(['put', 'patch'], '/inscriptions/current', [AdmissionController::class, 'update']);
    Route::get('/inscriptions/status', [AdmissionController::class, 'status']);

    Route::get('/student/dashboard', [StudentController::class, 'dashboard']);
    Route::get('/student/payments', [StudentController::class, 'payments']);
    Route::post('/student/payments/proof', [StudentController::class, 'storePaymentProof']);
    Route::get('/student/documents', [StudentController::class, 'documents']);
    Route::post('/student/documents', [StudentController::class, 'storeDocument']);
    Route::get('/student/comments', [StudentController::class, 'comments']);
    Route::post('/student/comments', [StudentController::class, 'storeComment']);
    Route::get('/student/notifications', [StudentController::class, 'notifications']);

    Route::get('/admin/overview', [AdminController::class, 'overview']);
    Route::get('/admin/users', [AdminController::class, 'users']);
    Route::get('/admin/admissions', [AdminController::class, 'admissions']);
    Route::patch('/admin/admissions/{application}/status', [AdminController::class, 'updateAdmissionStatus']);
    Route::get('/admin/content', [AdminController::class, 'content']);
    Route::get('/admin/audit', [AdminController::class, 'audit']);
});
