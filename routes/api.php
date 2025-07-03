<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\WorkspaceController;
use App\Http\Controllers\Api\ProjectStatusController;
use App\Http\Controllers\Api\WorkspaceMemberController;

// Public Routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Authenticated Routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Member Access
    Route::get('/workspaces/member', [WorkspaceController::class, 'MemberWorkspaces']);
    Route::get('/projects/member', [ProjectController::class, 'MemberProjects']);
    Route::get('/tasks/member', [TaskController::class, 'MemberTasks']);
    Route::apiResource('tasks', TaskController::class);
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);

    // Owner Access
    Route::apiResource('users', UserController::class);
    Route::apiResource('workspaces', WorkspaceController::class);
    Route::apiResource('projects', ProjectController::class);
    Route::apiResource('project-statuses', ProjectStatusController::class);
    Route::delete('/project-statuses', [ProjectStatusController::class, 'destroyMultiple']);
    Route::delete('/workspace-remove-members/{workspace}', [WorkspaceMemberController::class, 'removeMembersFromWorkspace']);
});
