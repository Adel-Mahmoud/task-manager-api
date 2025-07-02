<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\Request;
use App\Models\WorkspaceMember;
use App\Http\Controllers\Controller;
use App\Http\Resources\WorkspaceResource;

class WorkspaceController extends Controller
{
    public $userId;

    public function __construct()
    {
        $this->userId = auth('sanctum')->id();
    }

    public function index()
    {
        $workspaces = Workspace::where('user_id', auth('sanctum')->id())->latest()->get();
        if($workspaces->isEmpty()) {
            return response()->json(['message' => 'No workspaces found for this account'], 404);
        }
        return WorkspaceResource::collection($workspaces);
    }

    public function MemberWorkspaces()
    {
//         $userId = $this->userId;
//         return $userId;
//         $workspaces = WorkspaceMember::with('workspace')
//             ->where('user_id', $userId)
//             ->get()
//             ->pluck('workspace');

//         return WorkspaceResource::collection($workspaces);
    }

    public function store(Request $request)
    {
        $userId = auth('sanctum')->id();
        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $workspace = Workspace::create([
            'user_id' => $userId,
            'name' => $data['name'],
        ]);

        return new WorkspaceResource($workspace);
    }

    public function show(Workspace $workspace)
    {
        $this->authorizeAccess($workspace);
        return new WorkspaceResource($workspace->load('members.user'));
    }

    public function update(Request $request, Workspace $workspace)
    {
        $this->authorizeAccess($workspace);
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
        ]);

        $workspace->update($validated);

        return new WorkspaceResource($workspace);
    }

    public function destroy(Workspace $workspace)
    {
        $this->authorizeAccess($workspace);
        $workspace->delete();

        return response()->json(['message' => 'Workspace deleted successfully']);
    }

    protected function authorizeAccess(Workspace $workspace)
    {
        abort_if($workspace->user_id !== auth('sanctum')->id(), 403, 'Unauthorized');
    }
}
