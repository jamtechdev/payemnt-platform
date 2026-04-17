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

export default function UserManagement({ users, roles, permissionMatrix }: { users: unknown; roles: unknown; permissionMatrix?: unknown }) {
    const rows = asArray(users);
    const roleRows = asArray(roles);
    const matrix = (permissionMatrix && typeof permissionMatrix === 'object' ? (permissionMatrix as LooseRecord) : {}) as LooseRecord;
    const matrixRoles = asArray(matrix.roles);
    const matrixRows = asArray(matrix.rows);
    const { auth } = usePage<PageProps>().props;
    const canManageUsers = auth.permissions.includes('users.edit') && ['admin', 'super_admin'].includes(auth.role ?? '');
    const roleOptions = useMemo(() => roleRows.map((r) => String(r.name ?? '')).filter(Boolean), [roleRows]);

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
                                    disabled={!canManageUsers}
                                >
                                    Make Admin
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => router.patch(route('admin.users.update', Number(row.id ?? 0)), { role: 'customer_service' })}
                                    disabled={!canManageUsers}
                                >
                                    Make CS
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => router.patch(route('admin.users.update', Number(row.id ?? 0)), { role: 'reconciliation_admin' })}
                                    disabled={!canManageUsers}
                                >
                                    Make Recon
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => router.delete(route('admin.users.destroy', Number(row.id ?? 0)))}
                                    disabled={!canManageUsers}
                                >
                                    Delete
                                </Button>
                            </div>
                        </div>
                    ),
                }))}
            />

            {canManageUsers && (
                <div className="mt-6 rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
                    <h3 className="mb-2 text-base font-semibold text-slate-900 dark:text-slate-100">Role assignment</h3>
                    <p className="mb-4 text-sm text-slate-500 dark:text-slate-400">Permissions now come from the assigned role, so every user with the same role inherits the same access.</p>
                    <div className="space-y-4">
                        {rows.map((row, idx) => {
                            const userId = Number(row.id ?? 0);
                            const roleName = Array.isArray(row.roles) && row.roles[0] && typeof row.roles[0] === 'object' ? String((row.roles[0] as LooseRecord).name ?? '') : '';

                            return (
                                <div key={String(row.id ?? idx)} className="rounded-lg border border-slate-200 p-3 dark:border-slate-700 dark:bg-slate-700">
                                    <div className="mb-2 text-sm font-medium text-slate-800 dark:text-slate-100">{String(row.name ?? 'Unknown')}</div>
                                    <div className="grid gap-2 md:grid-cols-[minmax(0,1fr)_auto] md:items-end">
                                        <div>
                                            <label className="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-300">Role</label>
                                            <select
                                                className="w-full rounded-md border border-slate-200 px-2 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                                                defaultValue={roleName}
                                                onChange={(e) => router.patch(route('admin.users.access-control.update', userId), { role: e.target.value })}
                                            >
                                                <option value="">Select role</option>
                                                {roleOptions.map((r) => (
                                                    <option key={r} value={r}>
                                                        {r}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
                                        <p className="text-xs text-slate-500 dark:text-slate-400">Role-based permissions only</p>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </div>
            )}

            {matrixRows.length > 0 && matrixRoles.length > 0 && (
                <div className="mt-6 rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
                    <h3 className="mb-2 text-base font-semibold text-slate-900 dark:text-slate-100">Roles & Permissions Matrix</h3>
                    <p className="mb-4 text-sm text-slate-500 dark:text-slate-400">Each row is a permission and each column is a role. Users inherit these permissions from their assigned role.</p>

                    <div className="overflow-x-auto">
                        <table className="min-w-full border-collapse text-sm">
                            <thead>
                                <tr className="bg-slate-50 dark:bg-slate-900/40">
                                    <th className="border border-slate-200 px-3 py-2 text-left font-semibold text-slate-700 dark:border-slate-700 dark:text-slate-200">Permission</th>
                                    <th className="border border-slate-200 px-3 py-2 text-left font-semibold text-slate-700 dark:border-slate-700 dark:text-slate-200">Function</th>
                                    {matrixRoles.map((role, idx) => (
                                        <th key={String(role.name ?? idx)} className="border border-slate-200 px-3 py-2 text-center font-semibold text-slate-700 dark:border-slate-700 dark:text-slate-200">
                                            {String(role.label ?? role.name ?? '-')}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {matrixRows.map((item, idx) => {
                                    const allowed = (item.allowed && typeof item.allowed === 'object' ? (item.allowed as LooseRecord) : {}) as LooseRecord;
                                    return (
                                        <tr key={String(item.permission ?? idx)} className="odd:bg-white even:bg-slate-50/50 dark:odd:bg-slate-800 dark:even:bg-slate-800/60">
                                            <td className="border border-slate-200 px-3 py-2 text-slate-800 dark:border-slate-700 dark:text-slate-100">{String(item.permission ?? '-')}</td>
                                            <td className="border border-slate-200 px-3 py-2 text-slate-600 dark:border-slate-700 dark:text-slate-300">{String(item.function ?? '-')}</td>
                                            {matrixRoles.map((role, roleIdx) => {
                                                const enabled = Boolean(allowed[String(role.name ?? '')]);
                                                return (
                                                    <td key={`${String(item.permission ?? idx)}-${String(role.name ?? roleIdx)}`} className="border border-slate-200 px-3 py-2 text-center dark:border-slate-700">
                                                        <span className={`inline-flex h-5 w-5 items-center justify-center rounded-full text-xs font-semibold ${enabled ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-slate-200 text-slate-500 dark:bg-slate-700 dark:text-slate-300'}`}>
                                                            {enabled ? 'Y' : '-'}
                                                        </span>
                                                    </td>
                                                );
                                            })}
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}
