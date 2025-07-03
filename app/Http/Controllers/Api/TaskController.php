<?php

namespace App\Http\Controllers\Api;

use App\Models\Task;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\TaskResource;

class TaskController extends Controller
{
    public $userId;

    public function __construct()
    {
        $this->userId = auth('sanctum')->id();
    }

    public function index()
    {
        $userId = $this->userId;
        $projectId = request()->query('project_id');

        $tasksQuery = Task::with(['status', 'project.workspace', 'workspaceMember.workspace'])
            ->whereHas('project.workspace', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            });

        if ($projectId) {
            $tasksQuery->where('project_id', $projectId);
        }

        $tasks = $tasksQuery->get();

        return TaskResource::collection($tasks);
    }

    public function MemberTasks(Request $request)
    {
        $projectId = $request->query('project_id');
        $userId = auth('sanctum')->id();

        $tasks = Task::where('project_id', $projectId)
            ->where('user_id', $userId)
            ->get();
        if ($tasks->isEmpty()) {
            return response()->json(['message' => 'No tasks found for this project'], 404);
        }
        return TaskResource::collection($tasks);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'status_id' => 'required|exists:project_statuses,id',
            'workspace_member_id' => 'required|exists:workspace_members,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'is_completed' => 'boolean',
        ]);

        $task = Task::create($data);

        return new TaskResource($task);
    }

    public function show(Task $task)
    {
        $this->authorizeAccess($task);
        return new TaskResource($task->load('status', 'project', 'workspaceMember'));
    }

    public function update(Request $request, Task $task)
    {
        $this->authorizeAccess($task);

        $data = $request->validate([
            'status_id' => 'sometimes|exists:project_statuses,id',
            'workspace_member_id' => 'sometimes|exists:workspace_members,id',
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'is_completed' => 'boolean',
        ]);

        $task->update($data);

        return new TaskResource($task);
    }

    // public function updateStatus(Request $request, Task $task)
    // {
    //     $data = $request->validate([
    //         'status_id' => 'required|exists:project_statuses,id',
    //     ]);

    //     $user = auth('sanctum')->user();

    //     if (!$user) {
    //         return response()->json(['message' => 'Unauthorized'], 401);
    //     }

    //     $workspaceMember = $user->workspaceMembers()
    //         ->where('id', $task->workspace_member_id)
    //         ->first();

    //     if (!$workspaceMember) {
    //         return response()->json(['message' => 'Forbidden'], 403);
    //     }

    //     $task->update([
    //         'status_id' => $data['status_id']
    //     ]);

    //     return new TaskResource($task);
    // }

    public function destroy(Task $task)
    {
        $this->authorizeAccess($task);

        $task->delete();

        return response()->json(['message' => 'Task deleted successfully']);
    }

    public function authorizeAccess(Task $task)
    {
        $userId = $this->userId;
        if (
            !$task->relationLoaded('project') ||
            !$task->project->relationLoaded('workspace')
        ) {
            $task->load('project.workspace');
        }
        $workspaceOwner = $task->project->workspace->user_id === $userId;

        if (!$workspaceOwner) {
            abort(403, 'Unauthorized');
        }
    }
}
