<?php

namespace App\Http\Controllers\Api;

use App\Models\Comment;
use App\Http\Controllers\Controller;
use App\Http\Resources\CommentResource;
use Illuminate\Http\Request;

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
        $comments = Comment::with(['user', 'children'])->where('task_id', $taskId)->whereNull('parent_id')->get();
        return CommentResource::collection($comments);
    }

    public function show(Comment $comment)
    {
        return new CommentResource($comment->load(['user', 'children']));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'task_id'   => 'required|exists:tasks,id',
            'content'   => 'required|string',
            'parent_id' => 'nullable|exists:comments,id',
        ]);

        $comment = Comment::create([
            'user_id'   => $this->userId,
            'task_id'   => $validated['task_id'],
            'content'   => $validated['content'],
            'parent_id' => $validated['parent_id'] ?? null,
        ]);

        return new CommentResource($comment->load(['user', 'children']));
    }

    public function destroy(Comment $comment)
    {
        $this->authorizeAccess($comment);
        $comment->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function authorizeAccess(Comment $comment)
    {
        if ($comment->user_id !== $this->userId) {
            abort(403, 'Unauthorized action.');
        }
    }
}
