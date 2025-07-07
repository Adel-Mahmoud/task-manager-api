<?php

namespace App\Http\Controllers\Api;

use App\Models\Project;
use Illuminate\Http\Request;
use App\Models\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectStatusResource;

class ProjectStatusController extends Controller
{
    public $userId;

    public function __construct()
    {
        $this->userId = auth('sanctum')->id();
    }

    public function index(Request $request)
    {
        $projectId = $request->query('project_id');
        $userId = $this->userId;
        $statuses = [];
        if ($projectId) {
            $statuses = ProjectStatus::where('project_id', $projectId)
                ->whereHas('project.workspace', function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                })
                ->get();
        }
        if ($statuses->isEmpty()) {
            return response()->json(['message' => 'No project statuses found'], 404);
        }
        return ProjectStatusResource::collection($statuses->load('project', 'project.workspace'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'names' => 'required|string|max:255',
        ]);

        $project = Project::where('id', $data['project_id'])
            ->whereHas('workspace', function ($query) {
                $query->where('user_id', $this->userId);
            })->first();

        if (!$project) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $names = array_filter(array_map('trim', explode(',', $data['names'])));

        if (count($names) > 1) {
            $status = collect($names)->map(function ($name) use ($data) {
                return ProjectStatus::create([
                    'project_id' => $data['project_id'],
                    'name' => $name,
                ]);
            });
            $firstStatus = $status->first();
        } else {
            $firstStatus = ProjectStatus::create([
                'project_id' => $data['project_id'],
                'name' => $names[0] ?? $data['names'],
            ]);
        }

        if (!$firstStatus) {
            return response()->json(['message' => 'Failed to create project status'], 500);
        }

        $firstStatus->load('project', 'project.workspace');

        return new ProjectStatusResource($firstStatus);
    }

    public function show(Request $request)
    {
        $data = $request->validate([
            'project_id' => 'required|exists:projects,id',
        ]);

        if ($data['project_id']) {
            $project = ProjectStatus::where('project_id', $data['project_id'])
                ->whereHas('project.workspace', function ($query) {
                    $query->where('user_id', $this->userId);
                })->first();

            if (!$project) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }
        $this->authorizeAccess($project);
        return new ProjectStatusResource($project->project->projectStatuses->load('project'));
    }

    public function update(Request $request, ProjectStatus $projectStatus)
    {
        $this->authorizeAccess($projectStatus);
        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $projectStatus->update($data);

        return new ProjectStatusResource($projectStatus->load('project'));
    }

    public function destroyMultiple(Request $request)
    {
        $projectId = $request->query('project_id');
        if (!$projectId) {
            return response()->json(['message' => 'Project ID is required'], 422);
        }
        $this->authorizeAccess(ProjectStatus::where('project_id', $projectId)->first());

        $statuses_id = array_filter(explode(',', $request->query('statuses_id')));

        if (count($statuses_id) === 0) {
            return response()->json(['message' => 'No statuses provided'], 422);
        }

        $statuses = ProjectStatus::whereIn('id', $statuses_id)->get();

        foreach ($statuses as $status) {
            $this->authorizeAccess($status);
        }

        ProjectStatus::whereIn('id', $statuses_id)->delete();

        return response()->json(['message' => 'Project statuses deleted']);
    }

    public function destroy(ProjectStatus $projectStatus)
    {
        $this->authorizeAccess($projectStatus);
        $projectStatus->delete();

        return response()->json(['message' => 'Project status deleted']);
    }

    protected function authorizeAccess(ProjectStatus $projectStatus)
    {
        $userId = $this->userId;
        $workspaceOwnerId = $projectStatus->project->workspace->user_id ?? null;
        abort_if($workspaceOwnerId !== $userId, 403, 'Unauthorized');
    }
}
