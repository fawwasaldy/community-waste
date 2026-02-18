<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create($request->validated());

        $token = auth()->login($user);

        return $this->respondWithToken($token, 201, 'User registered successfully.');
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        if (! $token = auth()->attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        return $this->respondWithToken($token, 200, 'Successfully logged in.');
    }

    public function logout(): JsonResponse
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out.']);
    }

    public function me(): JsonResponse
    {
        return response()->json([
            'message' => 'User retrieved successfully.',
            'data' => auth()->user(),
        ]);
    }

    public function refresh(): JsonResponse
    {
        return $this->respondWithToken(auth()->refresh(), 200, 'Token refreshed successfully.');
    }

    protected function respondWithToken(string $token, int $status = 200, string $message = 'Successfully authenticated.'): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
        ], $status);
    }
}
