<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('/timetable', [App\Http\Controllers\ApiController::class, 'timetable'])->name('api.timetable');
Route::get('/lessons', [App\Http\Controllers\ApiController::class, 'lessons'])->name('api.lessons');
Route::get('/groups', [App\Http\Controllers\ApiController::class, 'groups'])->name('api.groups');
Route::middleware('throttle:hour')->group(function () {
    Route::get('/update', function () {
        try {
            $exitCode = Artisan::call('update:lessons');
            $output = Artisan::output();

            // Parse the output for better formatting
            $lines = array_filter(explode("\n", trim($output)));

            return response()->json([
                'status' => 'completed',
                'exit_code' => $exitCode,
                'timestamp' => now()->toISOString(),
                'output' => [
                    'raw' => $output,
                    'formatted_lines' => $lines
                ],
                'summary' => [
                    'total_lines' => count($lines),
                    'execution_time' => 'Command completed successfully'
                ]
            ], 200)->setEncodingOptions(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'timestamp' => now()->toISOString(),
                'error' => [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ], 500)->setEncodingOptions(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
    })->name('api.update');
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
