<?php

namespace App\Http\Controllers\Api;

use App\Models\ProjectStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\ProjectStatusResource;

class ProjectStatusController extends Controller
{
    public function index()
    {
        return ProjectStatusResource::collection(ProjectStatus::all());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'name' => 'required|string|max:255',
        ]);

        $status = ProjectStatus::create($data);

        return new ProjectStatusResource($status);
    }

    public function show(ProjectStatus $projectStatus)
    {
        return new ProjectStatusResource($projectStatus);
    }

    public function update(Request $request, ProjectStatus $projectStatus)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $projectStatus->update($data);

        return new ProjectStatusResource($projectStatus);
    }

    public function destroy(ProjectStatus $projectStatus)
    {
        $projectStatus->delete();

        return response()->noContent();
    }
}
