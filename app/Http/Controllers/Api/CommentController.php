<?php

namespace App\Http\Controllers\Api;

use App\Models\Task;
use App\Models\Comment;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\CommentResource;

class CommentController extends Controller
{
    public $userId;

    public function __construct()
    {
        $this->userId = auth('sanctum')->id();
    }

    public function index(Request $request)
    {
        $taskId = $request->query('task_id');

        $task = Task::with('project.workspace')->findOrFail($taskId);

        if (
            $this->userId !== $task->project->workspace->user_id &&
            !$task->comments()->where('user_id', $this->userId)->exists()
        ) {
            abort(403, 'Unauthorized action');
        }

        $comments = Comment::with(['user', 'task', 'children', 'parent'])
            ->where('task_id', $taskId)
            ->whereNull('parent_id')
            ->get();

        if ($comments->isEmpty()) {
            return response()->json(['message' => 'No comments found for this task'], 404);
        }

        return CommentResource::collection($comments->load(['user', 'children']));
    }

    public function show(Comment $comment)
    {
        $this->authorizeAccess($comment);
        return new CommentResource($comment->load(['user', 'children']));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'task_id'   => 'required|exists:tasks,id',
            'content'   => 'required|string',
            'parent_id' => 'nullable|exists:comments,id',
        ]);

        $taskId = $validated['task_id'];
        $task = Task::find($taskId);
        if (!$task) {
            return response()->json(['message' => 'Task not found'], 404);
        }
        if ($task->project->workspace->user_id !== $this->userId && $task->user_id !== $this->userId) {
            return response()->json(['message' => 'Unauthorized action'], 403);
        }

        $comment = Comment::create([
            'user_id'   => $this->userId,
            'task_id'   => $validated['task_id'],
            'content'   => $validated['content'],
            'parent_id' => $validated['parent_id'] ?? null,
        ]);

        return new CommentResource($comment->load(['user', 'children', 'task']));
    }

    public function destroy(Comment $comment)
    {
        $this->authorizeAccess($comment);
        $comment->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function authorizeAccess(Comment $comment)
    {
        if (
            $comment->user_id !== $this->userId &&
            $comment->task->project->workspace->user_id !== $this->userId
        ) {
            abort(403, 'Unauthorized action');
        }
    }
}
