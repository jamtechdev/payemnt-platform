<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\AuthTokenResource;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\PortalPassword;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class AuthController extends BaseApiController
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', PortalPassword::defaults()],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'status' => 'active',
            'is_active' => true,
        ]);

        $token = $user->createToken($validated['device_name'] ?? 'admin-api')->plainTextToken;
        AuditLog::record('api_register', $user, [], ['source' => 'api'], $user);

        return response()->json([
            'status' => 'success',
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => new AuthTokenResource($user),
            ],
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        return $this->issueToken($request);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink(['email' => $validated['email']]);

        if ($status !== Password::RESET_LINK_SENT) {
            return response()->json([
                'status' => 'error',
                'error_code' => 'RESET_LINK_FAILED',
                'message' => __($status),
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => __($status),
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PortalPassword::defaults()],
        ]);

        $status = Password::reset(
            $validated,
            function (User $user, string $password): void {
                $user->forceFill(['password' => Hash::make($password)])->save();
                $user->tokens()->delete();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json([
                'status' => 'error',
                'error_code' => 'PASSWORD_RESET_FAILED',
                'message' => __($status),
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => __($status),
        ]);
    }

    private function issueToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $user = User::query()->where('email', $validated['email'])->first();
        if ($user && $user->isLocked()) {
            return response()->json([
                'status' => 'error',
                'error_code' => 'ACCOUNT_LOCKED',
                'message' => 'Account is locked. Try again later.',
            ], 423);
        }

        if (! Auth::attempt(['email' => $validated['email'], 'password' => $validated['password']])) {
            if ($user) {
                $user->incrementLoginAttempts();
                AuditLog::record('api_login_failed', $user, [], ['source' => 'api'], $user);
            }
            return response()->json([
                'status' => 'error',
                'error_code' => 'INVALID_CREDENTIALS',
                'message' => 'Invalid credentials.',
            ], 401);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (! $user->is_active) {
            return response()->json([
                'status' => 'error',
                'error_code' => 'USER_INACTIVE',
                'message' => 'User account is inactive.',
            ], 403);
        }

        $user->resetLoginAttempts();
        $user->forceFill(['last_login_at' => now()])->save();
        AuditLog::record('api_login', $user, [], ['device_name' => $validated['device_name'] ?? 'admin-api'], $user);

        $token = $user->createToken($validated['device_name'] ?? 'admin-api')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => new AuthTokenResource($user),
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        AuditLog::record('api_logout', $request->user(), [], ['source' => 'api'], $request->user());
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Token revoked.',
        ]);
    }
}
