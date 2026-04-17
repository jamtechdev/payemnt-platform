import EntityListCard from '@/components/admin/EntityListCard';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/AdminLayout';
import { PageProps } from '@/Types';
import { router, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';

type LooseRecord = Record<string, unknown>;

function asArray(input: unknown): LooseRecord[] {
    if (Array.isArray(input)) return input as LooseRecord[];
    if (input && typeof input === 'object' && Array.isArray((input as { data?: unknown }).data)) {
        return (input as { data: LooseRecord[] }).data;
    }
    return [];
}

export default function UserManagement({ users, roles, permissionMatrix }: { users: unknown; roles: unknown; permissionMatrix?: unknown }) {
    const rows = asArray(users);
    const roleRows = asArray(roles);
    const matrix = (permissionMatrix && typeof permissionMatrix === 'object' ? (permissionMatrix as LooseRecord) : {}) as LooseRecord;
    const matrixRoles = asArray(matrix.roles);
    const matrixRows = asArray(matrix.rows);
    const { auth } = usePage<PageProps>().props;
    const canManageUsers = auth.permissions.includes('users.edit') && ['admin', 'super_admin'].includes(auth.role ?? '');
    const roleOptions = useMemo(() => roleRows.map((r) => String(r.name ?? '')).filter(Boolean), [roleRows]);
    const [openPermissionUserId, setOpenPermissionUserId] = useState<number | null>(null);
    const [openRoleUserId, setOpenRoleUserId] = useState<number | null>(null);

    const currentRoleName = (row: LooseRecord): string =>
        Array.isArray(row.roles) && row.roles[0] && typeof row.roles[0] === 'object' ? String((row.roles[0] as LooseRecord).name ?? '') : '';

    const canManageTargetUser = (target: LooseRecord): boolean => {
        if (!canManageUsers) return false;
        const actorId = Number(auth.user?.id ?? 0);
        const targetId = Number(target.id ?? 0);
        if (actorId > 0 && targetId > 0 && actorId === targetId) return false;

        const actorRole = auth.role ?? '';
        const targetRole = currentRoleName(target);
        if (actorRole === 'admin' && targetRole === 'super_admin') return false;
        return true;
    };

    const effectivePermissionsForRole = (roleName: string): string[] =>
        matrixRows
            .filter((item) => {
                const allowed = (item.allowed && typeof item.allowed === 'object' ? (item.allowed as LooseRecord) : {}) as LooseRecord;
                return Boolean(allowed[roleName]);
            })
            .map((item) => String(item.permission ?? ''))
            .filter(Boolean);

    return (
        <AdminLayout title="User management">
            <EntityListCard
                title="Admin users"
                emptyText="No users found."
                items={rows.map((row, idx) => {
                    const userId = Number(row.id ?? 0);
                    const roleName = currentRoleName(row);
                    const rolePermissions = effectivePermissionsForRole(roleName);

                    return {
                        key: String(row.id ?? idx),
                        content: (
                            <div className="space-y-4">
                                <div className="flex flex-col items-start justify-between gap-4 md:flex-row md:items-center">
                                    <div>
                                        <p className="font-medium text-slate-900 dark:text-slate-100">{String(row.name ?? 'Unknown')}</p>
                                        <p className="text-sm text-slate-500 dark:text-slate-400">{String(row.email ?? '-')}</p>
                                        <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">Role: {roleName || '-'}</p>
                                    </div>
                                    <Badge variant="outline">{String(row.is_active === false ? 'inactive' : 'active')}</Badge>
                                    <div className="flex flex-col items-start gap-2 md:flex-row md:items-center">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={() => setOpenPermissionUserId((prev) => (prev === userId ? null : userId))}
                                        >
                                            View Permissions
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={() => setOpenRoleUserId((prev) => (prev === userId ? null : userId))}
                                            disabled={!canManageTargetUser(row)}
                                        >
                                            Manage Role
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={() => {
                                                const ok = window.confirm('Are you sure you want to delete this user? This action cannot be undone.');
                                                if (!ok) return;

                                                router.delete(route('admin.users.destroy', userId));
                                            }}
                                            disabled={!canManageTargetUser(row)}
                                        >
                                            Delete
                                        </Button>
                                    </div>
                                </div>

                                {openRoleUserId === userId && (
                                    <div className="rounded-lg border border-slate-200 p-3 dark:border-slate-700 dark:bg-slate-700/40">
                                        <p className="mb-2 text-xs text-slate-500 dark:text-slate-300">Change role using quick buttons:</p>
                                        <div className="flex flex-wrap gap-2">
                                            {roleOptions.map((option) => (
                                                <Button
                                                    key={`${userId}-${option}`}
                                                    type="button"
                                                    variant={option === roleName ? 'default' : 'outline'}
                                                    size="sm"
                                                    onClick={() => router.patch(route('admin.users.access-control.update', userId), { role: option })}
                                                    disabled={!canManageTargetUser(row)}
                                                >
                                                    {option}
                                                </Button>
                                            ))}
                                        </div>
                                    </div>
                                )}

                                {openPermissionUserId === userId && (
                                    <div className="rounded-lg border border-slate-200 p-3 dark:border-slate-700 dark:bg-slate-700/40">
                                        <p className="mb-2 text-sm font-medium text-slate-800 dark:text-slate-100">Effective permissions</p>
                                        <p className="mb-3 text-xs text-slate-500 dark:text-slate-400">
                                            Showing permissions for role <span className="font-semibold">{roleName || '-'}</span>.
                                        </p>
                                        <div className="overflow-x-auto">
                                            <table className="min-w-full border-collapse text-sm">
                                                <thead>
                                                    <tr className="bg-slate-50 dark:bg-slate-900/40">
                                                        <th className="border border-slate-200 px-3 py-2 text-left font-semibold text-slate-700 dark:border-slate-700 dark:text-slate-200">
                                                            Permission
                                                        </th>
                                                        <th className="border border-slate-200 px-3 py-2 text-left font-semibold text-slate-700 dark:border-slate-700 dark:text-slate-200">
                                                            Function
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {rolePermissions.map((permission) => (
                                                        <tr
                                                            key={`${userId}-${permission}`}
                                                            className="odd:bg-white even:bg-slate-50/50 dark:odd:bg-slate-800 dark:even:bg-slate-800/60"
                                                        >
                                                            <td className="border border-slate-200 px-3 py-2 text-slate-800 dark:border-slate-700 dark:text-slate-100">
                                                                {permission}
                                                            </td>
                                                            <td className="border border-slate-200 px-3 py-2 text-slate-600 dark:border-slate-700 dark:text-slate-300">
                                                                {permission.replaceAll('.', ' ').replaceAll('_', ' ')}
                                                            </td>
                                                        </tr>
                                                    ))}
                                                    {rolePermissions.length === 0 && (
                                                        <tr>
                                                            <td
                                                                colSpan={2}
                                                                className="border border-slate-200 px-3 py-2 text-slate-500 dark:border-slate-700 dark:text-slate-400"
                                                            >
                                                                No permissions found for this role.
                                                            </td>
                                                        </tr>
                                                    )}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                )}
                            </div>
                        ),
                    };
                })}
            />
        </AdminLayout>
    );
}
