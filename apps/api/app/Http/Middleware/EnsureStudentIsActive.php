<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudentIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user('student') && $request->user('student')->status === 'Aktif', 403, 'Akun murid tidak aktif.');

        return $next($request);
    }
}
