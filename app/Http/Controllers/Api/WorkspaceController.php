<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WorkspaceResource;
use App\Models\Workspace;
use Illuminate\Http\Request;

class WorkspaceController extends Controller
{
    public function index()
    {
        $workspaces = Workspace::where('user_id', auth()->id())->latest()->get();
        return WorkspaceResource::collection($workspaces);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $workspace = Workspace::create([
            'user_id' => auth()->id(),
            'name' => $validated['name'],
        ]);

        return new WorkspaceResource($workspace);
    }

    public function show(Workspace $workspace)
    {
        $this->authorizeAccess($workspace);

        return new WorkspaceResource($workspace);
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
        // $this->authorizeAccess($workspace);

        $workspace->delete();

        return response()->json(['message' => 'Workspace deleted successfully']);
    }

    protected function authorizeAccess(Workspace $workspace)
    {
        abort_if($workspace->user_id !== auth()->id(), 403, 'Unauthorized');
    }
}
