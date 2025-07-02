<?php

namespace App\Http\Controllers\Api;

use App\Models\Task;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\TaskResource;

class TaskController extends Controller
{
    public function index()
    {
        $userId = auth('sanctum')->id();
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

    public function myMemberTasks()
    {
        $userId = auth('sanctum')->id();

        $tasks = Task::with(['status', 'project.workspace', 'workspaceMember.workspace'])
            ->whereHas('workspaceMember', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->get();

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
        return new TaskResource($task->load('status', 'project', 'workspaceMember'));
    }

    public function update(Request $request, Task $task)
    {
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

    public function updateStatus(Request $request, Task $task)
    {
        $data = $request->validate([
            'status_id' => 'required|exists:project_statuses,id',
        ]);

        $user = auth('sanctum')->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $workspaceMember = $user->workspaceMembers()
            ->where('id', $task->workspace_member_id)
            ->first();

        if (!$workspaceMember) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $task->update([
            'status_id' => $data['status_id']
        ]);

        return new TaskResource($task);
    }

    public function destroy(Task $task)
    {
        $task->delete();

        return response()->noContent();
    }
}
