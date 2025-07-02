<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\WorkspaceController;
use App\Http\Controllers\Api\ProjectStatusController;
use App\Http\Controllers\Api\WorkspaceMemberController;
use App\Http\Controllers\Api\TaskUserController;
// Authenticated routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth: ')->group(function () {
    Route::get('/test', function () {
        return response()->json(['message' => 'API is working by sanctum']);
    });
    // User authentication routes
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
    // Resource routes
    Route::apiResource('users', UserController::class);
    Route::apiResource('workspaces', WorkspaceController::class);
    Route::get('/workspaces/member', [WorkspaceController::class, 'myMemberWorkspaces']);
    Route::apiResource('workspace-members', WorkspaceMemberController::class)->only(['index', 'store', 'destroy']);
    Route::get('/projects/member', [ProjectController::class,'myMemberProjects']);
    Route::apiResource('projects', ProjectController::class);
    Route::apiResource('project-statuses', ProjectStatusController::class);
    Route::apiResource('tasks', TaskController::class);
    Route::apiResource('task-users', TaskUserController::class);
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);
});
