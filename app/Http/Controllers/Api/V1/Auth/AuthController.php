<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\V1\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    #[OA\Post(
        path: '/api/v1/auth/register',
        operationId: 'authRegister',
        summary: 'Register a new user and issue an API token.',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                examples: [
                    new OA\Examples(
                        example: 'standard_registration',
                        summary: 'Standard user registration',
                        value: [
                            'name' => 'Jane Doe',
                            'email' => 'jane@example.com',
                            'password' => 'secret123',
                            'password_confirmation' => 'secret123',
                        ]
                    ),
                    new OA\Examples(
                        example: 'finance_registration',
                        summary: 'Finance user registration',
                        value: [
                            'name' => 'Alex Finance',
                            'email' => 'alex.finance@example.com',
                            'password' => 'Billing2026!',
                            'password_confirmation' => 'Billing2026!',
                        ]
                    ),
                ],
                required: ['name', 'email', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Jane Doe'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'jane@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'secret123'),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'secret123'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'User registered.',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/AuthTokenResponse',
                    examples: [
                        new OA\Examples(
                            example: 'registered_user',
                            summary: 'User created with token',
                            value: [
                                'user' => [
                                    'id' => 1,
                                    'name' => 'Jane Doe',
                                    'email' => 'jane@example.com',
                                    'email_verified_at' => null,
                                    'created_at' => '2026-02-27T12:00:00Z',
                                ],
                                'token' => '1|abc123',
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation error.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::query()->create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
        ], 201);
    }

    #[OA\Post(
        path: '/api/v1/auth/login',
        operationId: 'authLogin',
        summary: 'Authenticate a user and issue an API token.',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                examples: [
                    new OA\Examples(
                        example: 'email_login',
                        summary: 'Standard login',
                        value: [
                            'email' => 'jane@example.com',
                            'password' => 'secret123',
                        ]
                    ),
                    new OA\Examples(
                        example: 'finance_login',
                        summary: 'Finance operator login',
                        value: [
                            'email' => 'alex.finance@example.com',
                            'password' => 'Billing2026!',
                        ]
                    ),
                ],
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'jane@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'secret123'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'User authenticated.',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/AuthTokenResponse',
                    examples: [
                        new OA\Examples(
                            example: 'authenticated_user',
                            summary: 'Login succeeded',
                            value: [
                                'user' => [
                                    'id' => 1,
                                    'name' => 'Jane Doe',
                                    'email' => 'jane@example.com',
                                    'email_verified_at' => null,
                                    'created_at' => '2026-02-27T12:00:00Z',
                                ],
                                'token' => '1|def456',
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Invalid credentials.', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
            new OA\Response(response: 422, description: 'Validation error.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function login(LoginRequest $request): JsonResponse
    {
        if (! Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        /** @var User $user */
        $user = Auth::user();
        $user->tokens()->delete();

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
        ]);
    }

    #[OA\Post(
        path: '/api/v1/auth/logout',
        operationId: 'authLogout',
        summary: 'Revoke the current access token.',
        security: [['sanctum' => []]],
        tags: ['Authentication'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'User logged out.',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/MessageResponse',
                    examples: [
                        new OA\Examples(
                            example: 'logout_success',
                            summary: 'Token revoked',
                            value: [
                                'message' => 'Logged out successfully.',
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedError')),
        ]
    )]
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    #[OA\Get(
        path: '/api/v1/auth/me',
        operationId: 'authMe',
        summary: 'Return the authenticated user.',
        security: [['sanctum' => []]],
        tags: ['Authentication'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Authenticated user.',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/UserDataResponse',
                    examples: [
                        new OA\Examples(
                            example: 'current_user',
                            summary: 'Current authenticated user',
                            value: [
                                'data' => [
                                    'id' => 1,
                                    'name' => 'Jane Doe',
                                    'email' => 'jane@example.com',
                                    'email_verified_at' => null,
                                    'created_at' => '2026-02-27T12:00:00Z',
                                ],
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedError')),
        ]
    )]
    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }
}
