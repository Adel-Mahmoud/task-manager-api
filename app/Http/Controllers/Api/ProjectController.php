<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Http\Resources\ProjectResource;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;

class ProjectController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $workspaceId = $request->query('workspace_id');
        $userId = auth('sanctum')->id();

        $projects = Project::whereHas('workspace', function ($query) use ($workspaceId, $userId) {
            $query->where('id', $workspaceId)->where('user_id', $userId);
        })->with('workspace', 'tasks', 'projectStatuses')->latest()->get();

        if ($projects->isEmpty()) {
            return $this->errorResponse('No projects found for this workspace', 404);
        }

        return $this->successResponse(ProjectResource::collection($projects), 'Projects retrieved successfully');
    }

    public function MemberProjects(Request $request)
    {
        $workspaceId = $request->query('workspace_id');
        $userId = auth('sanctum')->id();

        $projectsQuery = Project::query()
            ->whereHas('tasks', function ($query) use ($userId) {
                $query->where('workspace_member_id', $userId);
            });

        if ($workspaceId) {
            $projectsQuery->where('workspace_id', $workspaceId);
        }

        $projects = $projectsQuery->with('workspace', 'tasks', 'projectStatuses')->get();

        if ($projects->isEmpty()) {
            return $this->errorResponse('No projects found for this workspace', 404);
        }

        return $this->successResponse(ProjectResource::collection($projects), 'Projects retrieved successfully');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'workspace_id' => 'required|exists:workspaces,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $project = Project::create($data);

        return $this->successResponse(new ProjectResource($project), 'Project created successfully', 201);
    }

    public function show(Project $project)
    {
        $response = $this->authorizeAccess($project);
        if ($response) return $response;

        return $this->successResponse(new ProjectResource($project->load('workspace', 'tasks', 'projectStatuses')), 'Project retrieved');
    }

    public function update(Request $request, Project $project)
    {
        $response = $this->authorizeAccess($project);
        if ($response) return $response;

        $data = $request->validate([
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $project->update($data);

        return $this->successResponse(new ProjectResource($project), 'Project updated successfully');
    }

    public function destroy(Project $project)
    {
        $response = $this->authorizeAccess($project);
        if ($response) return $response;

        $project->delete();

        return $this->successResponse(null, 'Project deleted successfully');
    }

    protected function authorizeAccess(Project $project)
    {
        $userId = auth('sanctum')->id();
        $workspaceOwnerId = $project->workspace->user_id ?? null;

        if ($workspaceOwnerId !== $userId) {
            return $this->errorResponse('Unauthorized', 403);
        }
    }
}
