<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProductionController extends Controller
{
    public function __invoke(): View
    {
        $checks = [
            'Application' => [
                'APP_ENV' => config('app.env'),
                'APP_DEBUG' => config('app.debug') ? 'true' : 'false',
                'APP_URL' => config('app.url'),
            ],
            'Base de donnees' => [
                'Connexion' => config('database.default'),
                'Nom' => config('database.connections.'.config('database.default').'.database'),
                'Test' => $this->databaseStatus(),
            ],
            'Stockage' => [
                'Disque public' => Storage::disk('public')->exists('.') ? 'accessible' : 'a verifier',
                'Chemin public' => config('filesystems.disks.public.root'),
                'URL publique' => config('filesystems.disks.public.url'),
                'Lien storage' => is_link(public_path('storage')) ? 'present' : 'a creer avec php artisan storage:link',
            ],
        ];

        return view('admin.production.index', compact('checks'));
    }

    private function databaseStatus(): string
    {
        try {
            DB::connection()->getPdo();

            return 'connecte';
        } catch (\Throwable) {
            return 'non connecte';
        }
    }
}
