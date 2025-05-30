<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [\App\Http\Controllers\PageController::class, 'home'])->name('home');

Route::get('/group/{group}', [\App\Http\Controllers\PageController::class, 'group'])->name('group');
Route::get('/teacher/{teacher}', [\App\Http\Controllers\PageController::class, 'teacher'])->name('teacher');
Route::get('/classroom/{classroom}', [\App\Http\Controllers\PageController::class, 'classroom'])->name('classroom');
