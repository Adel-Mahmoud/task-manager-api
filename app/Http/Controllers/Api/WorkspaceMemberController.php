<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\Request;
use App\Models\WorkspaceMember;
use App\Http\Controllers\Controller;
use App\Http\Resources\WorkspaceMemberResource;
use App\Traits\ApiResponse;

class WorkspaceMemberController extends Controller
{

    use ApiResponse;

    public function index(Request $request)
    {
        $request->validate([
            'workspace_id' => 'required|exists:workspaces,id',
        ]);

        $members = WorkspaceMember::with('user', 'workspace')
            ->whereHas('workspace', function ($query) {
                $query->where('user_id', auth('sanctum')->id());
            })
            ->where('workspace_id', $request->query('workspace_id'))
            ->get();

        return $this->successResponse(WorkspaceMemberResource::collection($members), 'Members retrieved successfully');
    }

    public function store(Request $request, Workspace $workspace)
    {
        $response = $this->authorizeAccess($workspace);
        if ($response) return $response;

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

        return $this->successResponse(WorkspaceMemberResource::collection(collect($addedMembers)), 'Members added successfully');
    }

    public function removeMembersFromWorkspace(Request $request, Workspace $workspace)
    {
        $response = $this->authorizeAccess($workspace);
        if ($response) return $response;

        $data = $request->validate([
            'emails' => 'required|string',
        ]);

        $emails = array_filter(array_map('trim', explode(',', $data['emails'])));

        if (empty($emails)) {
            return $this->errorResponse('No emails provided', 400);
        }

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

        return $this->successResponse($removedMembers, 'Members removed successfully');
    }

    public function rejectMember($id)
    {
        $workspaceMember = WorkspaceMember::where('workspace_id', $id)
            ->where('user_id', auth('sanctum')->id())
            ->first();

        if (!$workspaceMember) {
            return $this->errorResponse('You are not a member of this workspace', 404);
        }

        $workspaceMember->delete();

        return $this->successResponse(null, 'Membership request rejected');
    }

    public function destroy(WorkspaceMember $workspaceMember)
    {
        $workspaceMember->delete();

        return $this->successResponse(null, 'Member removed');
    }

    protected function authorizeAccess(Workspace $workspace)
    {
        if ($workspace->user_id !== auth('sanctum')->id()) {
            return $this->errorResponse('Unauthorized', 403);
        }
    }
}
