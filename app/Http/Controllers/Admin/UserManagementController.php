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
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/SuperAdmin/UserManagement', [
            'users' => User::query()->with(['roles'])->paginate(15),
            'roles' => Role::query()->orderBy('name')->get(['id', 'name']),
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

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'role' => ['nullable', Rule::in(Role::query()->pluck('name')->all())],
            'is_active' => ['nullable', 'boolean'],
        ]);

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

        $validated = $request->validate([
            'role' => ['nullable', Rule::in(Role::query()->pluck('name')->all())],
        ]);

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

        if ((int) $request->user()->id === (int) $user->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User deleted.');
    }

    private function ensureAdminActor(Request $request): void
    {
        abort_unless($request->user()?->hasAnyRole(['admin', 'super_admin']), 403);
    }
}
