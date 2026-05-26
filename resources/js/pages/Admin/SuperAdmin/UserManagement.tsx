import AdminLayout from '@/layouts/AdminLayout';
import DataTable from '@/components/shared/DataTable';
import { createColumnHelper } from '@tanstack/react-table';
import { router, usePage, Link } from '@inertiajs/react';
import { PageProps } from '@/Types';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Eye, Trash2, UserX, Plus } from 'lucide-react';

interface UserRow {
    id: number;
    name: string;
    email: string;
    is_active: boolean;
    roles: { id: number; name: string }[];
    preferred_currency?: string;
}

interface PaginatedUsers {
    data: UserRow[];
    current_page: number;
    last_page: number;
    from: number;
    to: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

const ROLE_OPTIONS = ['super_admin', 'reconciliation_admin', 'customer_service'];

const ROLE_COLORS: Record<string, string> = {
    super_admin: 'bg-purple-100 text-purple-700 ring-purple-200',
    reconciliation_admin: 'bg-blue-100 text-blue-700 ring-blue-200',
    customer_service: 'bg-emerald-100 text-emerald-700 ring-emerald-200',
};

export default function UserManagement({ users }: { users: PaginatedUsers; roles: unknown; permissionMatrix?: unknown }) {
    const rows = users?.data ?? [];
    const { auth } = usePage<PageProps>().props;

    const columnHelper = createColumnHelper<UserRow>();

    const currentRoleName = (row: UserRow): string =>
        Array.isArray(row.roles) && row.roles[0] ? row.roles[0].name : '';

    const columns = [
        columnHelper.accessor((row) => row.name, {
            id: 'name', header: 'Name',
            cell: (info) => <span className="font-medium">{info.getValue()}</span>,
        }),
        columnHelper.accessor((row) => row.email, {
            id: 'email', header: 'Email',
            cell: (info) => <span className="text-xs text-muted-foreground">{info.getValue()}</span>,
        }),
        columnHelper.accessor((row) => currentRoleName(row), {
            id: 'role', header: 'Role',
            cell: (info) => {
                const role = info.getValue();
                return (
                    <span className={`inline-block rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset ${ROLE_COLORS[role] || 'bg-slate-100 text-slate-600 ring-slate-200'}`}>
                        {role.replaceAll('_', ' ')}
                    </span>
                );
            },
        }),
        columnHelper.accessor((row) => row.is_active, {
            id: 'status', header: 'Status',
            cell: (info) => {
                const active = info.getValue();
                return active !== false ? (
                    <span className="inline-block rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-200">Active</span>
                ) : (
                    <span className="inline-block rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-200">Inactive</span>
                );
            },
        }),
        columnHelper.display({
            id: 'actions', header: 'Actions',
            cell: (info) => {
                const row = info.row.original;
                const userId = row.id;
                const isSelf = Number(auth.user?.id ?? 0) === userId;

                return (
                    <div className="flex items-center gap-1.5" onClick={(e) => e.stopPropagation()}>
                        <Link href={route('admin.users.show', userId)}>
                            <Button size="sm" variant="ghost">
                                <Eye className="h-3.5 w-3.5" />
                            </Button>
                        </Link>
                        {!isSelf && (
                            <Button size="sm" variant="ghost" onClick={() => {
                                if (confirm('Deactivate this user?')) {
                                    router.post(route('admin.users.deactivate', userId), {}, { preserveScroll: true });
                                }
                            }}>
                                <UserX className="h-3.5 w-3.5" />
                            </Button>
                        )}
                        {!isSelf && (
                            <Button size="sm" variant="ghost" onClick={() => {
                                if (confirm('Delete this user? This cannot be undone.')) {
                                    router.delete(route('admin.users.destroy', userId), { preserveScroll: true });
                                }
                            }}>
                                <Trash2 className="h-3.5 w-3.5" />
                            </Button>
                        )}
                    </div>
                );
            },
        }),
    ];

    return (
        <AdminLayout title="User management">
            <div className="mb-4 flex items-center justify-between">
                <div className="grid flex-1 gap-4 md:grid-cols-4">
                    <Card className="border-blue-200/70 bg-blue-50/50">
                        <CardContent className="pt-4">
                            <p className="text-xs uppercase tracking-wide text-muted-foreground">Total users</p>
                            <p className="mt-1 text-2xl font-semibold">{users.total ?? rows.length}</p>
                        </CardContent>
                    </Card>
                    <Card className="border-emerald-200/70 bg-emerald-50/50">
                        <CardContent className="pt-4">
                            <p className="text-xs uppercase tracking-wide text-muted-foreground">Active</p>
                            <p className="mt-1 text-2xl font-semibold text-emerald-600">{rows.filter((r) => r.is_active !== false).length}</p>
                        </CardContent>
                    </Card>
                    <Card className="border-red-200/70 bg-red-50/50">
                        <CardContent className="pt-4">
                            <p className="text-xs uppercase tracking-wide text-muted-foreground">Inactive</p>
                            <p className="mt-1 text-2xl font-semibold text-red-600">{rows.filter((r) => r.is_active === false).length}</p>
                        </CardContent>
                    </Card>
                    <Card className="border-violet-200/70 bg-violet-50/50">
                        <CardContent className="pt-4">
                            <p className="text-xs uppercase tracking-wide text-muted-foreground">Roles</p>
                            <p className="mt-1 text-2xl font-semibold">{ROLE_OPTIONS.length}</p>
                        </CardContent>
                    </Card>
                </div>
                <div className="ml-4 shrink-0">
                    <Link href={route('admin.users.create')}>
                        <Button>
                            <Plus className="mr-1 h-4 w-4" /> Add User
                        </Button>
                    </Link>
                </div>
            </div>

            <DataTable columns={columns} data={rows} showHeader showRowCount stripedRows clickableRows={false} emptyMessage="No users found." stickyHeader compact />

            {users.last_page > 1 && (
                <div className="mt-4 flex items-center justify-between text-sm text-muted-foreground">
                    <span>Showing {users.from ?? 0}–{users.to ?? 0} of {users.total ?? 0}</span>
                    <div className="flex gap-1">
                        {users.links.map((link, i) => (
                            <button key={i} disabled={!link.url}
                                onClick={() => link.url && router.get(link.url, {}, { preserveState: true })}
                                className={`rounded px-3 py-1 text-xs border ${link.active ? 'bg-primary text-primary-foreground border-primary' : 'border-input hover:bg-accent disabled:opacity-40'}`}
                                dangerouslySetInnerHTML={{ __html: link.label }} />
                        ))}
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}
