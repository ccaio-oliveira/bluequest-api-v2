<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChallengeController;
use App\Http\Controllers\Api\CompletionController;
use App\Http\Controllers\Api\InviteController;
use App\Http\Controllers\Api\SocialAuthController;
use App\Http\Controllers\Api\TodayController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/auth/{provider}', [SocialAuthController::class, 'store']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/today', [TodayController::class, 'index']);
    Route::post('/completions', [CompletionController::class, 'store']);

    Route::get('/challenges', [ChallengeController::class, 'index']);
    Route::post('/challenges', [ChallengeController::class, 'store']);
    Route::get('/challenges/{challenge}', [ChallengeController::class, 'show']);
    Route::get('/challenges/{challenge}/invite', [InviteController::class, 'show']);
    Route::post('/challenges/{challenge}/invite/rotate', [InviteController::class, 'rotate']);
    Route::get('/invites/{code}', [InviteController::class, 'preview']);
    Route::post('/invites/{code}/accept', [InviteController::class, 'accept']);
});
