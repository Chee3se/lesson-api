<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('/timetable', [App\Http\Controllers\ApiController::class, 'timetable'])->name('api.timetable');
Route::get('/lessons', [App\Http\Controllers\ApiController::class, 'lessons'])->name('api.lessons');
Route::get('/groups', [App\Http\Controllers\ApiController::class, 'groups'])->name('api.groups');
Route::middleware('throttle:hour')->group(function () {
});

Route::get('/update', function () {
    try {
        $exitCode = Artisan::call('update:lessons');
        $output = Artisan::output();

        return response()->json([
            'status' => 'completed',
            'exit_code' => $exitCode,
            'output' => $output
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
})->name('api.update');
Route::get('/migrate', function () {
    try {
        $exitCode = Artisan::call('migrate:fresh', ['--force' => true]);
        $output = Artisan::output();

        return response()->json([
            'status' => 'completed',
            'exit_code' => $exitCode,
            'output' => $output
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
