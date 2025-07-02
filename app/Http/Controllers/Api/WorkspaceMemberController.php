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

    public function removeMembersFromWorkspace(Request $request, Workspace $workspace)
    {
        $this->authorizeAccess($workspace);
        $data = $request->validate([
            'workspace_id' => 'required|string|max:255',
            'emails' => 'required|string',
        ]);
        // Validate the workspace ID
        if ($workspace->id !== (int)$data['workspace_id']) {
            return response()->json(['message' => 'Invalid workspace ID'], 400);
        }
        // Validate the emails
        $emails = array_filter(array_map('trim', explode(',', $data['emails'])));
        if (empty($emails)) {
            return response()->json(['message' => 'No emails provided'], 400);
        }
        // remove members from the workspace
        $users = User::whereIn('email', $emails)->get();
        $removedMembers = [];
        foreach ($users as $user) {
            if ($user->id !== auth('sanctum')->id()) {
                $member = WorkspaceMember::where('workspace_id', $workspace->id)
                    ->where('user_id', $user->id)
                    ->first();

                if ($member) {
                    $member->delete();
                    $removedMembers[] = $user;
                }
            }
        }
        return response()->json([
            'message' => 'Members removed successfully',
            'removed_members' => $removedMembers,
        ]);
    }

    public function destroy(WorkspaceMember $workspaceMember)
    {
        $workspaceMember->delete();

        return response()->json(['message' => 'Member removed']);
    }

    protected function authorizeAccess(Workspace $workspace)
    {
        abort_if($workspace->user_id !== auth('sanctum')->id(), 403, 'Unauthorized');
    }
}
