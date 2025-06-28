<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Http\Resources\ProjectResource;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index()
    {
        return ProjectResource::collection(
            Project::with(['users', 'tasks'])->get()
        );
    }

    public function show(Project $project)
    {
        return new ProjectResource($project->load(['users', 'tasks']));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date'  => 'nullable|date',
            'end_date'    => 'nullable|date',
            'members'     => 'nullable|array',
            'members.*'   => 'exists:users,id',
        ]);

        $project = Project::create($data);

        if (!empty($data['members'])) {
            $project->users()->sync($data['members']);
        }

        return new ProjectResource($project->load('users'));
    }
}
