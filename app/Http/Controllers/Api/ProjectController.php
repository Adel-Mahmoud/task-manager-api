<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Http\Resources\ProjectResource;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $workspaceId = $request->query('workspace_id');

        $userId = auth('sanctum')->id();

        $projects = Project::whereHas('workspace', function ($query) use ($workspaceId, $userId) {
            $query->where('id', $workspaceId)->where('user_id', $userId);
        })->with('workspace', 'tasks','projectStatuses')->latest()->get();

        if ($projects->isEmpty()) {
            return response()->json(['message' => 'No projects found for this workspace'], 404);
        }

        return ProjectResource::collection($projects);
    }

    public function MemberProjects(Request $request)
    {
        $workspaceId = $request->query('workspace_id');
        $userId = auth('sanctum')->id();

        $projects = Project::where('workspace_id', $workspaceId)
            ->whereHas('tasks', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->with('workspace','tasks')
            ->get();
        if ($projects->isEmpty()) {
            return response()->json(['message' => 'No projects found for this workspace'], 404);
        }

        return ProjectResource::collection($projects);
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

        return new ProjectResource($project);
    }

    public function show(Project $project)
    {
        $this->authorizeAccess($project);
        return new ProjectResource($project->load('workspace', 'tasks','projectStatuses'));
    }

    public function update(Request $request, Project $project)
    {
        $this->authorizeAccess($project);
        $data = $request->validate([
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $project->update($data);

        return new ProjectResource($project);
    }

    public function destroy(Project $project)
    {
        $this->authorizeAccess($project);
        $project->delete();

        return response()->json(['message' => 'Project deleted']);
    }

    protected function authorizeAccess(Project $project)
    {
        $userId = auth('sanctum')->id();
        $workspaceOwnerId = $project->workspace->user_id ?? null;

        abort_if($workspaceOwnerId !== $userId, 403, 'Unauthorized');
    }
}
