<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\AuthTokenResource;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use OpenApi\Attributes as OA;

class AuthController extends BaseApiController
{
    #[OA\Post(
        path: '/api/v1/auth/register',
        operationId: 'authRegister',
        summary: 'Register user with email and password',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'password', type: 'string', format: 'password'),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'User registered',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'token', type: 'string', example: '1|sanctum-token-value'),
                                new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
                                new OA\Property(
                                    property: 'user',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer', example: 1),
                                        new OA\Property(property: 'name', type: 'string', example: 'Alex Admin'),
                                        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@local.test'),
                                        new OA\Property(property: 'role', type: 'string', nullable: true, example: 'admin'),
                                    ],
                                    type: 'object'
                                ),
                            ],
                            type: 'object'
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'The password field confirmation does not match.'),
                        new OA\Property(property: 'errors', type: 'object'),
                    ]
                )
            ),
        ]
    )]
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', PasswordRule::min(12)->mixedCase()->numbers()->symbols()],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'status' => 'active',
            'is_active' => true,
        ]);
        UserProfile::query()->firstOrCreate(['user_id' => $user->id]);

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

    #[OA\Post(
        path: '/api/v1/auth/login',
        operationId: 'unifiedLogin',
        summary: 'Login with email and password',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'password', type: 'string'),
                    new OA\Property(property: 'device_name', type: 'string', example: 'admin-api'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Token created',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'token', type: 'string', example: '1|sanctum-token-value'),
                                new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
                                new OA\Property(
                                    property: 'user',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer', example: 1),
                                        new OA\Property(property: 'name', type: 'string', example: 'Alex Admin'),
                                        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@local.test'),
                                        new OA\Property(property: 'role', type: 'string', nullable: true, example: 'admin'),
                                    ],
                                    type: 'object'
                                ),
                            ],
                            type: 'object'
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Invalid credentials',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'error'),
                        new OA\Property(property: 'error_code', type: 'string', example: 'INVALID_CREDENTIALS'),
                        new OA\Property(property: 'message', type: 'string', example: 'Invalid credentials.'),
                    ]
                )
            ),
            new OA\Response(
                response: 423,
                description: 'Account locked',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'error'),
                        new OA\Property(property: 'error_code', type: 'string', example: 'ACCOUNT_LOCKED'),
                        new OA\Property(property: 'message', type: 'string', example: 'Account is locked. Try again later.'),
                    ]
                )
            ),
        ]
    )]
    public function login(Request $request): JsonResponse
    {
        return $this->issueToken($request);
    }

    #[OA\Post(
        path: '/api/v1/auth/forgot-password',
        operationId: 'authForgotPassword',
        summary: 'Send password reset link',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email'],
                properties: [new OA\Property(property: 'email', type: 'string', format: 'email')]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Reset link sent',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'We have emailed your password reset link.'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Reset link failed',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'error'),
                        new OA\Property(property: 'error_code', type: 'string', example: 'RESET_LINK_FAILED'),
                        new OA\Property(property: 'message', type: 'string', example: 'We can not find a user with that email address.'),
                    ]
                )
            ),
        ]
    )]
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

    #[OA\Post(
        path: '/api/v1/auth/reset-password',
        operationId: 'authResetPassword',
        summary: 'Reset password using reset token',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['token', 'email', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'token', type: 'string'),
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'password', type: 'string', format: 'password'),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Password reset',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Your password has been reset.'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Password reset failed',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'error'),
                        new OA\Property(property: 'error_code', type: 'string', example: 'PASSWORD_RESET_FAILED'),
                        new OA\Property(property: 'message', type: 'string', example: 'This password reset token is invalid.'),
                    ]
                )
            ),
        ]
    )]
    public function resetPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(12)->mixedCase()->numbers()->symbols()],
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

    #[OA\Post(
        path: '/api/v1/auth/logout',
        operationId: 'workspaceTokenLogout',
        summary: 'Revoke current Sanctum token',
        security: [['sanctum' => []]],
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Token revoked',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Token revoked.'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.'),
                    ]
                )
            ),
        ]
    )]
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
