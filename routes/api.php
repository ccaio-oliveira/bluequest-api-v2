<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChallengeController;
use App\Http\Controllers\Api\CompletionController;
use App\Http\Controllers\Api\InviteController;
use App\Http\Controllers\Api\ParticipantController;
use App\Http\Controllers\Api\SocialAuthController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\TodayController;
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
    Route::put('/challenges/{challenge}', [ChallengeController::class, 'update']);
    Route::delete('/challenges/{challenge}', [ChallengeController::class, 'destroy']);
    Route::post('/challenges/{challenge}/end', [ChallengeController::class, 'end']);
    Route::post('/challenges/{challenge}/tasks', [TaskController::class, 'store']);
    Route::get('/challenges/{challenge}/invite', [InviteController::class, 'show']);
    Route::put('/challenges/{challenge}/invite', [InviteController::class, 'update']);
    Route::post('/challenges/{challenge}/invite/rotate', [InviteController::class, 'rotate']);
    Route::delete('/challenges/{challenge}/participants/{user}', [ParticipantController::class, 'destroy']);
    Route::put('/tasks/{task}', [TaskController::class, 'update']);
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy']);
    Route::get('/invites/{code}', [InviteController::class, 'preview']);
    Route::post('/invites/{code}/accept', [InviteController::class, 'accept']);
});
