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
    Route::get('/workspace/member', [WorkspaceController::class, 'MemberWorkspaces']); 
    Route::delete('/workspace/member/{memberId}', [WorkspaceMemberController::class, 'rejectMember']);
    Route::get('/projects/member', [ProjectController::class, 'MemberProjects']);
    Route::get('/tasks/member', [TaskController::class, 'MemberTasks']);
    Route::get('/task/{task}', [TaskController::class, 'MemberTask']);
    Route::put('/task-status/{task}', [TaskController::class, 'taskStatus']);
    Route::apiResource('tasks', TaskController::class);
    Route::post('/comments/create', [CommentController::class, 'store']);
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);

    // Owner Access
    Route::apiResource('workspaces', WorkspaceController::class);
    Route::post('/workspace/member/{workspace}', [WorkspaceMemberController::class, 'store']);
    Route::apiResource('projects', ProjectController::class);
    Route::apiResource('project-statuses', ProjectStatusController::class);
    Route::delete('/project-statuses', [ProjectStatusController::class, 'destroyMultiple']);
    Route::post('/workspace-remove-members/{workspace}', [WorkspaceMemberController::class, 'removeMembersFromWorkspace']);
    // Admin Access
    Route::apiResource('users', UserController::class);
});
