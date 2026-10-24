<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\AuthUserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::query()->create([
            'name'     => $request->validated('name'),
            'email'    => $request->validated('email'),
            'password' => Hash::make($request->validated('password')),
        ]);
        $user->markLastLogin();

        $token = $user->createToken('subtrack-app')->plainTextToken;

        return response()->json([
            'message' => 'Account created successfully.',
            'data'    => [
                'user'  => new AuthUserResource($user),
                'token' => $token,
            ],
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only(['email', 'password']);

        $user = User::query()->where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'These credentials do not match our records.',
            ], 401);
        }

        $user->markLastLogin();

        $token = $user->createToken('subtrack-app')->plainTextToken;

        return response()->json([
            'message' => 'Logged in successfully.',
            'data'    => [
                'user'  => new AuthUserResource($user),
                'token' => $token,
            ],
        ]);
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json([
            'data' => new AuthUserResource($request->user()),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }
}