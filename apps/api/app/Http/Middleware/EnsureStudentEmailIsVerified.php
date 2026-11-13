<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudentEmailIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user('student')?->hasVerifiedEmail(), 403, 'Verifikasi email sebelum melanjutkan.');

        return $next($request);
    }
}
