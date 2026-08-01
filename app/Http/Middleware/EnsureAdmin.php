<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isAdmin()) {
            auth()->logout();

            return redirect()
                ->route('admin.login')
                ->withErrors(['email' => 'Acces reserve a l administration.']);
        }

        return $next($request);
    }
}
