import AdminLayout from '@/layouts/AdminLayout';
import EntityListCard from '@/components/admin/EntityListCard';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { router, usePage } from '@inertiajs/react';
import { PageProps } from '@/Types';
import { useMemo } from 'react';

type LooseRecord = Record<string, unknown>;

function asArray(input: unknown): LooseRecord[] {
    if (Array.isArray(input)) return input as LooseRecord[];
    if (input && typeof input === 'object' && Array.isArray((input as { data?: unknown }).data)) {
        return (input as { data: LooseRecord[] }).data;
    }
    return [];
}

export default function UserManagement({ users, roles, permissions }: { users: unknown; roles: unknown; permissions: unknown }) {
    const rows = asArray(users);
    const roleRows = asArray(roles);
    const permissionRows = asArray(permissions);
    const { auth } = usePage<PageProps>().props;
    const isSuperAdmin = auth.role === 'super_admin';
    const roleOptions = useMemo(() => roleRows.map((r) => String(r.name ?? '')).filter(Boolean), [roleRows]);
    const permissionOptions = useMemo(() => permissionRows.map((p) => String(p.name ?? '')).filter(Boolean), [permissionRows]);

    return (
        <AdminLayout title="User management">
            <EntityListCard
                title="Admin users"
                emptyText="No users found."
                items={rows.map((row, idx) => ({
                    key: String(row.id ?? idx),
                    content: (
                        <div className="flex md:items-center items-start justify-between flex-col md:flex-row gap-4">
                            <div>
                                <p className="font-medium text-slate-900 dark:text-slate-100">{String(row.name ?? 'Unknown')}</p>
                                <p className="text-sm text-slate-500 dark:text-slate-400">{String(row.email ?? '-')}</p>
                            </div>
                            <Badge variant="outline">{String(row.is_active === false ? 'inactive' : 'active')}</Badge>
                            <div className="flex md:items-center items-start gap-2 flex-col md:flex-row">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => router.patch(route('admin.users.update', Number(row.id ?? 0)), { role: 'admin' })}
                                    disabled={!isSuperAdmin}
                                >
                                    Make Admin
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => router.patch(route('admin.users.update', Number(row.id ?? 0)), { role: 'customer_service' })}
                                    disabled={!isSuperAdmin}
                                >
                                    Make CS
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => router.patch(route('admin.users.update', Number(row.id ?? 0)), { role: 'reconciliation_admin' })}
                                    disabled={!isSuperAdmin}
                                >
                                    Make Recon
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => router.delete(route('admin.users.destroy', Number(row.id ?? 0)))}
                                    disabled={!isSuperAdmin}
                                >
                                    Delete
                                </Button>
                            </div>
                        </div>
                    ),
                }))}
            />

            {isSuperAdmin && (
                <div className="mt-6 rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
                    <h3 className="mb-3 text-base font-semibold text-slate-900 dark:text-slate-100">Role & Permission section</h3>
                    <div className="space-y-4">
                        {rows.map((row, idx) => {
                            const userId = Number(row.id ?? 0);
                            const roleName = Array.isArray(row.roles) && row.roles[0] && typeof row.roles[0] === 'object' ? String((row.roles[0] as LooseRecord).name ?? '') : '';
                            const userPerms =
                                Array.isArray(row.permissions) && row.permissions.length > 0
                                    ? row.permissions
                                          .map((p) => (typeof p === 'object' ? String((p as LooseRecord).name ?? '') : ''))
                                          .filter(Boolean)
                                    : [];

                            return (
                                <div key={String(row.id ?? idx)} className="rounded-lg border border-slate-200 p-3 dark:border-slate-700 dark:bg-slate-700">
                                    <div className="mb-2 text-sm font-medium text-slate-800 dark:text-slate-100">{String(row.name ?? 'Unknown')}</div>
                                    <div className="grid gap-2 md:grid-cols-2">
                                        <div>
                                            <label className="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-300">Role</label>
                                            <select
                                                className="w-full rounded-md border border-slate-200 px-2 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                                                defaultValue={roleName}
                                                onChange={(e) =>
                                                    router.patch(route('admin.users.access-control.update', userId), {
                                                        role: e.target.value,
                                                        permissions: userPerms,
                                                    })
                                                }
                                            >
                                                <option value="">Select role</option>
                                                {roleOptions.map((r) => (
                                                    <option key={r} value={r}>
                                                        {r}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
                                        <div>
                                            <label className="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-300">Permissions</label>
                                            <div className="max-h-36 overflow-y-auto rounded-md border border-slate-200 p-2 dark:border-slate-600 dark:bg-slate-800">
                                                <div className="grid grid-cols-1 gap-1">
                                                    {permissionOptions.map((perm) => {
                                                        const checked = userPerms.includes(perm);
                                                        return (
                                                            <label key={perm} className="flex items-center gap-2 text-xs text-slate-700 dark:text-slate-200">
                                                                <input
                                                                    type="checkbox"
                                                                    defaultChecked={checked}
                                                                    onChange={(e) => {
                                                                        const next = e.target.checked
                                                                            ? [...new Set([...userPerms, perm])]
                                                                            : userPerms.filter((p) => p !== perm);
                                                                        router.patch(route('admin.users.access-control.update', userId), {
                                                                            role: roleName,
                                                                            permissions: next,
                                                                        });
                                                                    }}
                                                                    className="accent-blue-500"
                                                                />
                                                                <span>{perm}</span>
                                                            </label>
                                                        );
                                                    })}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}
