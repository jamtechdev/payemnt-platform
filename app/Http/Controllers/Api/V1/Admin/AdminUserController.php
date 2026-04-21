<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\Admin\StoreAdminUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminUserController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $users = User::query()->with('roles')->latest()->paginate(min((int) $request->integer('per_page', 20), 100));
        return $this->paginated($users);
    }

    public function store(StoreAdminUserRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'status' => 'active',
            'is_active' => true,
        ]);
        $user->syncRoles($validated['roles'] ?? [Role::findByName('customer_service')->name]);

        return $this->success($user->load('roles'), 201);
    }

    public function show(User $user): JsonResponse
    {
        return $this->success($user->load('roles'));
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'status' => ['sometimes', 'in:active,inactive,suspended'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ]);
        $user->update($validated);

        if (isset($validated['roles'])) {
            $user->syncRoles($validated['roles']);
        }

        return $this->success($user->fresh('roles'));
    }

    public function destroy(User $user): JsonResponse
    {
        $user->delete();
        return $this->success(['message' => 'User deleted']);
    }
}
