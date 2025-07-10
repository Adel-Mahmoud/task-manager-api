<?php

namespace App\Http\Controllers\Api;

use App\Models\Task;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Traits\ApiResponse;

class TaskController extends Controller
{
    use ApiResponse;

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

        return $this->successResponse(
            TaskResource::collection($tasks->load('status', 'project.workspace', 'workspaceMember.workspace', 'comments'))
        );
    }

    public function MemberTasks(Request $request)
    {
        $projectId = $request->query('project_id');
        $userId = $this->userId;

        $tasks = Task::where('project_id', $projectId)
            ->where('workspace_member_id', $userId)
            ->get();

        if ($tasks->isEmpty()) {
            return $this->errorResponse('No tasks found for this project', 404);
        }

        return $this->successResponse(
            TaskResource::collection($tasks->load('status', 'project.workspace', 'workspaceMember.workspace', 'comments'))
        );
    }

    public function MemberTask($taskId)
    {
        $userId = $this->userId;

        $task = Task::where('id', $taskId)
            ->where('workspace_member_id', $userId)
            ->with('status', 'project.workspace', 'workspaceMember.workspace', 'comments')
            ->first();

        if (!$task) {
            return $this->errorResponse('Task not found or unauthorized', 404);
        }

        return $this->successResponse(new TaskResource($task));
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

        return $this->successResponse(
            new TaskResource($task->load('status', 'project.workspace', 'workspaceMember.workspace', 'comments')),
            'Task created successfully',
            201
        );
    }

    public function show(Task $task)
    {
        $response = $this->authorizeAccess($task);
        if ($response) return $response;

        return $this->successResponse(
            new TaskResource($task->load('status', 'project', 'workspaceMember', 'comments'))
        );
    }

    public function update(Request $request, Task $task)
    {
        $response = $this->authorizeAccess($task);
        if ($response) return $response;

        $data = $request->validate([
            'status_id' => 'sometimes|exists:project_statuses,id',
            'workspace_member_id' => 'sometimes|exists:workspace_members,id',
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'is_completed' => 'boolean',
        ]);

        $task->update($data);

        return $this->successResponse(
            new TaskResource($task->load('status', 'project.workspace', 'workspaceMember.workspace')),
            'Task updated successfully'
        );
    }

    public function taskStatus(Request $request, Task $task)
    {
        $data = $request->validate([
            'status_id' => 'required|exists:project_statuses,id',
        ]);

        if (!$task) {
            return $this->errorResponse('Task not found', 404);
        }

        if ($task->status_id === (int) $data['status_id']) {
            return $this->errorResponse('Task status is already set to this status', 400);
        }

        if (!$task->relationLoaded('workspaceMember')) {
            $task->load('workspaceMember');
        }

        $userId = auth('sanctum')->id();
        if (!$userId) {
            return $this->errorResponse('Unauthorized', 401);
        }

        if (!$task->workspaceMember || $task->workspaceMember->user_id !== $userId) {
            return $this->errorResponse('Forbidden', 403);
        }

        $task->status_id = $data['status_id'];
        $task->save();

        return $this->successResponse(
            new TaskResource($task->load('status', 'project.workspace', 'workspaceMember.workspace', 'comments')),
            'Task status updated successfully'
        );
    }

    public function destroy(Task $task)
    {
        $response = $this->authorizeAccess($task);
        if ($response) return $response;

        $task->delete();

        return $this->successResponse(null, 'Task deleted successfully');
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
            return $this->errorResponse('Unauthorized', 403);
        }
    }
}
