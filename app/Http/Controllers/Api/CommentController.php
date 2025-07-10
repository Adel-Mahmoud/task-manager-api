<?php

namespace App\Http\Controllers\Api;

use App\Models\Task;
use App\Models\Comment;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\CommentResource;
use App\Traits\ApiResponse;

class CommentController extends Controller
{
    use ApiResponse;

    public $userId;

    public function index(Request $request)
    {
        $taskId = $request->query('task_id');

        $task = Task::with('project.workspace')->find($taskId);

        if (! $task) {
            return $this->errorResponse('Task not found', 404);
        }

        if (
            $this->userId !== $task->project->workspace->user_id &&
            !$task->comments()->where('user_id', $this->userId)->exists()
        ) {
            return $this->errorResponse('Unauthorized action', 403);
        }

        $comments = Comment::with(['user', 'task', 'children', 'parent'])
            ->where('task_id', $taskId)
            ->whereNull('parent_id')
            ->get();

        if ($comments->isEmpty()) {
            return $this->errorResponse('No comments found for this task', 404);
        }

        return $this->successResponse(CommentResource::collection($comments->load(['user', 'children'])));
    }

    public function show(Comment $comment)
    {
        if (! $this->isAuthorized($comment)) {
            return $this->errorResponse('Unauthorized action', 403);
        }

        return $this->successResponse(new CommentResource($comment->load(['user', 'children'])));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'task_id'   => 'required|exists:tasks,id',
            'content'   => 'required|string',
            'parent_id' => 'nullable|exists:comments,id',
        ]);

        $task = Task::with('project.workspace')->find($validated['task_id']);

        if (! $task) {
            return $this->errorResponse('Task not found', 404);
        }

        if (
            $task->project->workspace->user_id !== $this->userId &&
            $task->workspace_member_id !== $this->userId
        ) {
            return $this->errorResponse('Unauthorized action', 403);
        }

        $comment = Comment::create([
            'user_id'   => $this->userId,
            'task_id'   => $validated['task_id'],
            'content'   => $validated['content'],
            'parent_id' => $validated['parent_id'] ?? null,
        ]);

        return $this->successResponse(new CommentResource($comment->load(['user', 'children', 'task'])), 'Comment created', 201);
    }

    public function destroy(Comment $comment)
    {
        if (! $this->isAuthorized($comment)) {
            return $this->errorResponse('Unauthorized action', 403);
        }

        $comment->delete();

        return $this->successResponse(null, 'Comment deleted');
    }

    private function isAuthorized(Comment $comment): bool
    {
        return $comment->user_id === $this->userId ||
               $comment->task->project->workspace->user_id === $this->userId;
    }
}
