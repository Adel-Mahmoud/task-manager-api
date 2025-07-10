<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Traits\ApiResponse;

class UserController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $users = User::latest()->get();
        return $this->successResponse(UserResource::collection($users), 'Users retrieved successfully');
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name'     => 'required|string|max:255',
                'email'    => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:6',
            ]);

            $validated['password'] = Hash::make($validated['password']);

            $user = User::create($validated);

            return $this->successResponse(new UserResource($user), 'User created successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create user', 500);
        }
    }

    public function show(User $user)
    {
        return $this->successResponse(new UserResource($user), 'User retrieved successfully');
    }

    public function update(Request $request, User $user)
    {
        try {
            $validated = $request->validate([
                'name'     => 'sometimes|required|string|max:255',
                'email'    => ['sometimes', 'required', 'email', Rule::unique('users')->ignore($user->id)],
                'password' => 'nullable|string|min:6',
            ]);

            if (isset($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            } else {
                unset($validated['password']);
            }

            $user->update($validated);

            return $this->successResponse(new UserResource($user), 'User updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update user', 500);
        }
    }

    public function destroy(User $user)
    {
        try {
            $user->delete();
            return $this->successResponse(null, 'User deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete user', 500);
        }
    }
}
