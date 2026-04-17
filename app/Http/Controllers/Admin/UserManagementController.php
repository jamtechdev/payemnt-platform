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
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    public function index(): Response
    {
        $roles = Role::query()->with('permissions:id,name')->orderBy('name')->get(['id', 'name']);
        $permissions = collect(config('admin_portal.permissions', []))
            ->filter(fn ($permission): bool => is_string($permission) && $permission !== '')
            ->values();

        return Inertia::render('Admin/SuperAdmin/UserManagement', [
            'users' => User::query()->with(['roles'])->paginate(15),
            'roles' => $roles->map(fn (Role $role) => ['id' => $role->id, 'name' => $role->name])->values(),
            'permissionMatrix' => $this->buildPermissionMatrix($roles, $permissions),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureAdminActor($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(Role::query()->pluck('name')->all())],
            'password' => ['nullable', 'string', 'min:12'],
        ]);
        $actor = $request->user();
        if ($this->isAdminButNotSuperAdmin($actor) && ($validated['role'] ?? null) === 'super_admin') {
            return back()->with('error', 'Admins cannot assign the super_admin role.');
        }

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password'] ?? 'ChangeMe12345!'),
        ]);
        $user->syncRoles([$validated['role']]);
        UserProfile::query()->firstOrCreate(['user_id' => $user->id]);
        AuditLog::record('created', $user, [], $user->toArray(), $request->user());

        return back()->with('success', 'User invited.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->ensureAdminActor($request);
        $actor = $request->user();
        if (! $this->canManageUser($actor, $user)) {
            return back()->with('error', 'You are not allowed to update this user.');
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'role' => ['nullable', Rule::in(Role::query()->pluck('name')->all())],
            'is_active' => ['nullable', 'boolean'],
        ]);
        if ($this->isAdminButNotSuperAdmin($actor) && ($validated['role'] ?? null) === 'super_admin') {
            return back()->with('error', 'Admins cannot assign the super_admin role.');
        }

        $user->update(collect($validated)->except(['role'])->all());
        if (! empty($validated['role'])) {
            $user->syncRoles([$validated['role']]);
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
        $this->ensureAdminActor($request);
        $actor = $request->user();
        if (! $this->canManageUser($actor, $user)) {
            return back()->with('error', 'You are not allowed to change access for this user.');
        }

        $validated = $request->validate([
            'role' => ['nullable', Rule::in(Role::query()->pluck('name')->all())],
        ]);
        if ($this->isAdminButNotSuperAdmin($actor) && ($validated['role'] ?? null) === 'super_admin') {
            return back()->with('error', 'Admins cannot assign the super_admin role.');
        }

        if (! empty($validated['role'])) {
            $user->syncRoles([$validated['role']]);
        }
        $user->syncPermissions([]);

        AuditLog::record('web_user_access_control_updated', $user, [], [
            'role' => $user->getRoleNames()->first(),
            'permissions' => $user->roles()->with('permissions')->get()->pluck('permissions')->flatten()->pluck('name')->unique()->values()->all(),
        ], $request->user());

        return back()->with('success', 'Role updated. Permissions are inherited from the assigned role.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->ensureAdminActor($request);

        if (! $this->canManageUser($request->user(), $user)) {
            return back()->with('error', 'You are not allowed to delete this user.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User deleted.');
    }

    private function ensureAdminActor(Request $request): void
    {
        abort_unless($request->user()?->hasAnyRole(['admin', 'super_admin']), 403);
    }

    private function canManageUser(?User $actor, User $target): bool
    {
        if (! $actor) {
            return false;
        }
        if ((int) $actor->id === (int) $target->id) {
            return false;
        }

        if ($actor->hasRole('super_admin')) {
            return true;
        }

        if ($actor->hasRole('admin')) {
            return ! $target->hasRole('super_admin');
        }

        return false;
    }

    private function isAdminButNotSuperAdmin(?User $actor): bool
    {
        return (bool) ($actor?->hasRole('admin') && ! $actor?->hasRole('super_admin'));
    }

    /**
     * @param Collection<int,Role> $roles
     * @param Collection<int,string> $permissions
     * @return array{roles: list<array{name:string,label:string}>, rows: list<array{permission:string,function:string,allowed: array<string,bool>}>}
     */
    private function buildPermissionMatrix(Collection $roles, Collection $permissions): array
    {
        $roleColumns = $roles
            ->map(fn (Role $role): array => [
                'name' => $role->name,
                'label' => (string) data_get(config('admin_portal.roles'), "{$role->name}.label", str_replace('_', ' ', $role->name)),
            ])
            ->values()
            ->all();

        $rows = $permissions->map(function (string $permission) use ($roles): array {
            $allowed = [];
            foreach ($roles as $role) {
                $allowed[$role->name] = $role->permissions->pluck('name')->contains($permission);
            }

            return [
                'permission' => $permission,
                'function' => (string) str_replace(['.', '_'], ' ', $permission),
                'allowed' => $allowed,
            ];
        })->values()->all();

        return [
            'roles' => $roleColumns,
            'rows' => $rows,
        ];
    }
}
