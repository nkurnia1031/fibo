<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response()->file(public_path('spa.html')));
Route::get('/{path}', fn () => response()->file(public_path('spa.html')))
    ->where('path', '^(?!api(?:/|$)|storage(?:/|$)|up(?:/|$)).*');
