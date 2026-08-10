<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CompletionController;
use App\Http\Controllers\Api\TodayController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/today', [TodayController::class, 'index']);
    Route::post('/completions', [CompletionController::class, 'store']);
});
