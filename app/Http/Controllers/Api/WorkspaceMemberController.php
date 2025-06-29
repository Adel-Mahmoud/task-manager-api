<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\WorkspaceMember;
use App\Http\Controllers\Controller;
use App\Http\Resources\WorkspaceMemberResource;

class WorkspaceMemberController extends Controller
{
    public function index(Request $request)
    {
        $workspaceId = $request->query('workspace_id');

        $members = WorkspaceMember::with('user')
            ->where('workspace_id', $workspaceId)
            ->get();

        return WorkspaceMemberResource::collection($members);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'workspace_id' => 'required|exists:workspaces,id',
            'user_id' => 'required|exists:users,id',
            'role' => 'in:owner,member',
        ]);

        $exists = WorkspaceMember::where('workspace_id', $data['workspace_id'])
            ->where('user_id', $data['user_id'])
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'User already a member'], 422);
        }

        $member = WorkspaceMember::create($data);

        return new WorkspaceMemberResource($member->load('user'));
    }

    public function destroy(WorkspaceMember $workspaceMember)
    {
        $workspaceMember->delete();

        return response()->json(['message' => 'Member removed']);
    }
}
