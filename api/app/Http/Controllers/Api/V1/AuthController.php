<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Auth\IssueTokenPair;
use App\Actions\Auth\RevokeUserTokens;
use App\Actions\Auth\RotateRefreshToken;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RefreshRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\Role;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(RegisterRequest $request, IssueTokenPair $issueTokenPair): JsonResponse
    {
        $user = User::create([
            ...$request->validated(),
            'role_id' => Role::where('name', 'member')->value('id'),
        ]);

        return response()->json([
            'user' => new UserResource($user),
            ...$issueTokenPair->handle($user),
        ], 201);
    }

    public function login(LoginRequest $request, IssueTokenPair $issueTokenPair): JsonResponse
    {
        $user = User::where('email', $request->validated('email'))->first();

        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            return ApiResponse::error('Invalid credentials.', 'INVALID_CREDENTIALS', 401);
        }

        return response()->json([
            'user' => new UserResource($user),
            ...$issueTokenPair->handle($user),
        ]);
    }

    public function refresh(RefreshRequest $request, RotateRefreshToken $rotateRefreshToken): JsonResponse
    {
        $pair = $rotateRefreshToken->handle($request->validated('refresh_token'));

        if (! $pair) {
            return ApiResponse::error('Refresh token is invalid or was reused.', 'REFRESH_REJECTED', 401);
        }

        return response()->json($pair);
    }

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user()->load('role.permissions'));
    }

    public function logout(Request $request, RevokeUserTokens $revokeUserTokens): JsonResponse
    {
        $revokeUserTokens->handle($request->user());

        return response()->json(['message' => 'Logged out everywhere.']);
    }
}
