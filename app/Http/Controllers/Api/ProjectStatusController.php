<?php

namespace App\Http\Controllers\Api;

use App\Models\Project;
use Illuminate\Http\Request;
use App\Models\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectStatusResource;
use App\Traits\ApiResponse;

class ProjectStatusController extends Controller
{
    use ApiResponse;

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
            return $this->errorResponse('No project statuses found', 404);
        }

        return $this->successResponse(ProjectStatusResource::collection($statuses->load('project', 'project.workspace')));
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
            return $this->errorResponse('Unauthorized', 403);
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
            return $this->errorResponse('Failed to create project status', 500);
        }

        $firstStatus->load('project', 'project.workspace');

        return $this->successResponse(new ProjectStatusResource($firstStatus), 'Project status created successfully', 201);
    }

    public function show(Request $request)
    {
        $data = $request->validate([
            'project_id' => 'required|exists:projects,id',
        ]);

        $projectStatus = ProjectStatus::where('project_id', $data['project_id'])
            ->whereHas('project.workspace', function ($query) {
                $query->where('user_id', $this->userId);
            })->first();

        if (!$projectStatus) {
            return $this->errorResponse('Unauthorized', 403);
        }

        $authCheck = $this->authorizeAccess($projectStatus);
        if ($authCheck) return $authCheck;

        return $this->successResponse(new ProjectStatusResource($projectStatus->project->projectStatuses->load('project')));
    }

    public function update(Request $request, ProjectStatus $projectStatus)
    {
        $authCheck = $this->authorizeAccess($projectStatus);
        if ($authCheck) return $authCheck;

        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $projectStatus->update($data);

        return $this->successResponse(new ProjectStatusResource($projectStatus->load('project')), 'Project status updated successfully');
    }

    public function destroyMultiple(Request $request)
    {
        $projectId = $request->query('project_id');

        if (!$projectId) {
            return $this->errorResponse('Project ID is required', 422);
        }

        $firstStatus = ProjectStatus::where('project_id', $projectId)->first();
        $authCheck = $this->authorizeAccess($firstStatus);
        if ($authCheck) return $authCheck;

        $statuses_id = array_filter(explode(',', $request->query('statuses_id')));

        if (count($statuses_id) === 0) {
            return $this->errorResponse('No statuses provided', 422);
        }

        $statuses = ProjectStatus::whereIn('id', $statuses_id)->get();

        foreach ($statuses as $status) {
            $authCheck = $this->authorizeAccess($status);
            if ($authCheck) return $authCheck;
        }

        ProjectStatus::whereIn('id', $statuses_id)->delete();

        return $this->successResponse(null, 'Project statuses deleted successfully');
    }

    public function destroy(ProjectStatus $projectStatus)
    {
        $authCheck = $this->authorizeAccess($projectStatus);
        if ($authCheck) return $authCheck;

        $projectStatus->delete();

        return $this->successResponse(null, 'Project status deleted successfully');
    }

    protected function authorizeAccess(ProjectStatus $projectStatus)
    {
        $userId = $this->userId;
        $workspaceOwnerId = $projectStatus->project->workspace->user_id ?? null;

        if ($workspaceOwnerId !== $userId) {
            return $this->errorResponse('Unauthorized', 403);
        }
        return null;
    }
}
