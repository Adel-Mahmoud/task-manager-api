<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\WorkspaceMember;
use App\Http\Controllers\Controller;
use App\Http\Resources\WorkspaceMemberResource;

class WorkspaceMemberController extends Controller
{
    public function index(Request $request)
    {
        $workspaceId = $request->query('workspace_id');

        $members = WorkspaceMember::with('user', 'workspace')
            ->where('workspace_id', $workspaceId)
            ->get();

        return WorkspaceMemberResource::collection($members);
    }

    public function myWorkspaces()
    {
        $userId = auth('sanctum')->id();

        $workspaces = WorkspaceMember::with('workspace')
            ->where('user_id', $userId)
            ->get()
            ->pluck('workspace'); 

        return response()->json($workspaces);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'workspace_id' => 'required|exists:workspaces,id',
            'emails' => 'required|string',
            'role' => 'in:owner,member',
        ]);

        $emails = array_filter(array_map('trim', explode(',', $data['emails'])));
        $users = User::whereIn('email', $emails)->get();

        $addedMembers = [];

        foreach ($users as $user) {
            $exists = WorkspaceMember::where('workspace_id', $data['workspace_id'])
                ->where('user_id', $user->id)
                ->exists();

            if (!$exists) {
                $member = WorkspaceMember::create([
                    'workspace_id' => $data['workspace_id'],
                    'user_id' => $user->id,
                    'role' => $data['role'] ?? 'member',
                ]);

                $addedMembers[] = $member->load('user');
            }
        }

        return WorkspaceMemberResource::collection(collect($addedMembers));
    }

    public function destroy(WorkspaceMember $workspaceMember)
    {
        $workspaceMember->delete();

        return response()->json(['message' => 'Member removed']);
    }
}
