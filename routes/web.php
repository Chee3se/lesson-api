<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [\App\Http\Controllers\PageController::class, 'home'])->name('home');

Route::get('/timetable', [\App\Http\Controllers\PageController::class, 'timetable'])->name('timetable');
