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

// Authenticated routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth: ')->group(function () {
    // User authentication routes
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
    // Owner routes
    Route::apiResource('users', UserController::class);
    Route::delete('workspace-remove-members/{workspace}', [WorkspaceMemberController::class, 'removeMembersFromWorkspace']);
    Route::apiResource('workspaces', WorkspaceController::class);
    Route::apiResource('projects', ProjectController::class);
    Route::delete('project-statuses', [ProjectStatusController::class, 'destroyMultiple']);
    Route::apiResource('project-statuses', ProjectStatusController::class);
    // Member routes
    Route::get('/workspaces/member', [WorkspaceController::class, 'MemberWorkspaces']);
    Route::apiResource('workspace-members', WorkspaceMemberController::class)->only(['index', 'store', 'destroy']);
    Route::get('/projects/member', [ProjectController::class, 'myMemberProjects']);
    Route::apiResource('tasks', TaskController::class);
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);
});
