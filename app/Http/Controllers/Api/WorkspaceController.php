<?php

namespace App\Http\Controllers\Api;

use App\Models\Workspace;
use Illuminate\Http\Request;
use App\Models\WorkspaceMember;
use App\Http\Controllers\Controller;
use App\Http\Resources\WorkspaceResource;
use App\Traits\ApiResponse;

class WorkspaceController extends Controller
{
    use ApiResponse;

    public $userId;

    public function __construct()
    {
        $this->userId = auth('sanctum')->id();
    }

    public function index()
    {
        $workspaces = Workspace::where('user_id', $this->userId)->latest()->get();

        if ($workspaces->isEmpty()) {
            return $this->errorResponse('No workspaces found for this account', 404);
        }

        return $this->successResponse(WorkspaceResource::collection($workspaces), 'Workspaces retrieved successfully');
    }

    public function MemberWorkspaces()
    {
        $workspaces = WorkspaceMember::with('workspace')
            ->where('user_id', $this->userId)
            ->get()
            ->pluck('workspace');

        return $this->successResponse(WorkspaceResource::collection($workspaces), 'Member workspaces retrieved successfully');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $workspace = Workspace::create([
            'user_id' => $this->userId,
            'name' => $data['name'],
        ]);

        return $this->successResponse(new WorkspaceResource($workspace->load('owner')), 'Workspace created successfully', 201);
    }

    public function show(Workspace $workspace)
    {
        $response = $this->authorizeAccess($workspace);
        if ($response) return $response;

        return $this->successResponse(new WorkspaceResource($workspace->load('owner', 'members', 'projects')), 'Workspace details retrieved successfully');
    }

    public function update(Request $request, Workspace $workspace)
    {
        $response = $this->authorizeAccess($workspace);
        if ($response) return $response;

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
        ]);

        $workspace->update($validated);

        return $this->successResponse(new WorkspaceResource($workspace->load('owner')), 'Workspace updated successfully');
    }

    public function destroy(Workspace $workspace)
    {
        $response = $this->authorizeAccess($workspace);
        if ($response) return $response;

        $workspace->delete();

        return $this->successResponse(null, 'Workspace deleted successfully');
    }

    protected function authorizeAccess(Workspace $workspace)
    {
        if ($workspace->user_id !== $this->userId) {
            return $this->errorResponse('Unauthorized', 403);
        }
    }
}
