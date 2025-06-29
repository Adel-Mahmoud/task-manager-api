<?php

namespace App\Http\Controllers\Api;

use App\Models\TaskUser;
use App\Http\Controllers\Controller;
use App\Http\Resources\TaskUserResource;
use Illuminate\Http\Request;

class TaskUserController extends Controller
{
    public function index()
    {
        return TaskUserResource::collection(TaskUser::all());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'task_id' => 'required|exists:tasks,id',
            'user_id' => 'required|exists:users,id',
        ]);

        $taskUser = TaskUser::create($data);

        return new TaskUserResource($taskUser);
    }

    public function show(TaskUser $taskUser)
    {
        return new TaskUserResource($taskUser);
    }

    public function destroy(TaskUser $taskUser)
    {
        $taskUser->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }
}