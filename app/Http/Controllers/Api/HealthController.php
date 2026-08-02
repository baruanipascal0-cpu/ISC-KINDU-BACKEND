<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class HealthController extends ApiController
{
    public function __invoke(): JsonResponse
    {
        $database = [
            'status' => 'error',
            'connection' => config('database.default'),
            'migrations' => 0,
            'admin_exists' => false,
        ];

        try {
            DB::connection()->getPdo();

            $database['status'] = 'ok';
            $database['migrations'] = Schema::hasTable('migrations')
                ? DB::table('migrations')->count()
                : 0;
            $database['admin_exists'] = Schema::hasTable('users')
                ? User::query()->where('role', 'admin')->exists()
                : false;
        } catch (Throwable) {
            $database['status'] = 'error';
        }

        $healthy = $database['status'] === 'ok';

        return $this->ok([
            'status' => $healthy ? 'ok' : 'error',
            'app' => config('app.name'),
            'environment' => config('app.env'),
            'database' => $database,
        ], null, $healthy ? 200 : 503);
    }
}
