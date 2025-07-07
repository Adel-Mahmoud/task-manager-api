<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\Request;
use App\Models\WorkspaceMember;
use App\Http\Controllers\Controller;
use App\Http\Resources\WorkspaceMemberResource;

class WorkspaceMemberController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'workspace_id' => 'required|exists:workspaces,id',
        ]);
        $members = WorkspaceMember::with('user', 'workspace')
            ->whereHas('workspace', function ($query) {
                $query->whare('user_id', auth('sanctum')->id());
            })
            ->where('workspace_id', $request->query('workspace_id'))
            ->get();

        return WorkspaceMemberResource::collection($members);
    }

    public function store(Request $request, Workspace $workspace)
    {
        $this->authorizeAccess($workspace);
        $data = $request->validate([
            'emails' => 'required|string',
        ]);

        $emails = array_filter(array_map('trim', explode(',', $data['emails'])));
        $users = User::whereIn('email', $emails)->get();

        $addedMembers = [];

        foreach ($users as $user) {
            $exists = WorkspaceMember::where('workspace_id', $workspace->id)
                ->where('user_id', $user->id)
                ->exists();

            if (!$exists) {
                $member = WorkspaceMember::create([
                    'workspace_id' => $workspace->id,
                    'user_id' => $user->id,
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
            'emails' => 'required|string',
        ]);
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

    public function rejectMember(Request $request, Workspace $workspace)
    {
        $workspaceMember = WorkspaceMember::where('workspace_id', $workspace->id)
            ->where('user_id', auth('sanctum')->id())
            ->first();
        if (!$workspaceMember) {
            return response()->json(['message' => 'You are not a member of this workspace'], 404);
        }
        $workspaceMember->delete();
        return response()->json(['message' => 'Membership request rejected']);
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
