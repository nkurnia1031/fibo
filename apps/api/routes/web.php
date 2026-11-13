<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response()->file(public_path('spa.html')));
Route::get('/student/password/reset', fn () => view('student-password-reset'))->name('student.password.reset');
Route::get('/{path}', fn () => response()->file(public_path('spa.html')))
    ->where('path', '^(?!api(?:/|$)|storage(?:/|$)|up(?:/|$)).*');
