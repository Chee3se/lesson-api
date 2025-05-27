<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('/timetable', [App\Http\Controllers\ApiController::class, 'timetable'])->name('api.timetable');
Route::get('/lessons', [App\Http\Controllers\ApiController::class, 'lessons'])->name('api.lessons');
Route::get('/groups', [App\Http\Controllers\ApiController::class, 'groups'])->name('api.groups');
Route::middleware('throttle:hour')->group(function () {
    Route::get('/update', function () {
        Artisan::call('update:lessons');
        return response()->json('Lessons have been updated!', 200);
    })->name('api.update');
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
