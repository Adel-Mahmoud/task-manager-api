<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Hash;
use App\Traits\ApiResponse;

class AuthController extends Controller
{
    use ApiResponse;

    public function register(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required',
                'email' => 'required|email|unique:users',
                'password' => 'required|min:6',
            ]);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
            ]);

            return $this->successResponse(new UserResource($user), 'User registered successfully', 201);
        } catch (\Throwable $e) {
            return $this->errorResponse('Registration failed', 400, $e->getMessage());
        }
    }

    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required'
            ]);

            $user = User::where('email', $request->email)->first();

            if (! $user || ! Hash::check($request->password, $user->password)) {
                return $this->errorResponse('Invalid credentials', 401);
            }

            $token = $user->createToken('api-token')->plainTextToken;

            return $this->successResponse([
                'token' => $token,
                'user' => new UserResource($user)
            ], 'Login successful');
        } catch (\Throwable $e) {
            return $this->errorResponse('Login failed', 400, $e->getMessage());
        }
    }

    public function user(Request $request)
    {
        try {
            return $this->successResponse(new UserResource(auth('sanctum')->user()), 'User fetched successfully');
        } catch (\Throwable $e) {
            return $this->errorResponse('Failed to fetch user', 400, $e->getMessage());
        }
    }

    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();
            return $this->successResponse(null, 'Logged out successfully');
        } catch (\Throwable $e) {
            return $this->errorResponse('Logout failed', 400, $e->getMessage());
        }
    }
}
