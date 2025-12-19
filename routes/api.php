<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LogController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {
    Route::middleware(['api.key', 'throttle:1000,1'])->group(function () {
        Route::post('/logs', [LogController::class, 'store']);
    });
    Route::get('/logs', [LogController::class, 'logsView']);
});
