<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/SuperAdmin/UserManagement', [
            'users' => User::query()->with(['roles', 'permissions'])->paginate(15),
            'roles' => Role::query()->orderBy('name')->get(['id', 'name']),
            'permissions' => Permission::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = User::query()->create([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'password' => Hash::make('ChangeMe12345!'),
        ]);
        $user->assignRole($request->string('role')->toString());
        UserProfile::query()->firstOrCreate(['user_id' => $user->id]);
        AuditLog::record('created', $user, [], $user->toArray(), $request->user());

        return back()->with('success', 'User invited.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $user->update($request->only(['name', 'email']));
        if ($request->filled('role')) {
            $user->syncRoles([$request->string('role')->toString()]);
        }

        return back()->with('success', 'User updated.');
    }

    public function deactivate(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->id === $user->id) {
            return back()->with('error', 'You cannot deactivate yourself.');
        }
        $user->update(['is_active' => false, 'remember_token' => null]);

        return back()->with('success', 'User deactivated.');
    }

    public function updateAccessControl(Request $request, User $user): RedirectResponse
    {
        if (! $request->user()->hasRole('super_admin')) {
            return back()->with('error', 'Only super admin can manage roles and permissions.');
        }

        $validated = $request->validate([
            'role' => ['nullable', Rule::in(Role::query()->pluck('name')->all())],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [Rule::in(Permission::query()->pluck('name')->all())],
        ]);

        if (! empty($validated['role'])) {
            $user->syncRoles([$validated['role']]);
        }
        $user->syncPermissions($validated['permissions'] ?? []);

        AuditLog::record('web_user_access_control_updated', $user, [], [
            'role' => $user->getRoleNames()->first(),
            'permissions' => $user->getAllPermissions()->pluck('name')->values()->all(),
        ], $request->user());

        return back()->with('success', 'Role and permissions updated.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ((int) $request->user()->id === (int) $user->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User deleted.');
    }
}
