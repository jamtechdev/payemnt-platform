import AdminLayout from '@/layouts/AdminLayout';
import { PageProps } from '@/Types';
import DataTable from '@/components/shared/DataTable';
import StatusBadge from '@/components/shared/StatusBadge';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { router, usePage } from '@inertiajs/react';
import { createColumnHelper } from '@tanstack/react-table';
import { Eye, Pencil, Trash2, Key, Users } from 'lucide-react';

type LooseRecord = Record<string, unknown>;

function asArray(input: unknown): LooseRecord[] {
    if (Array.isArray(input)) return input as LooseRecord[];
    if (input && typeof input === 'object' && Array.isArray((input as { data?: unknown }).data)) {
        return (input as { data: LooseRecord[] }).data;
    }
    return [];
}

export default function PartnerList({ partners }: { partners: unknown }) {
    const rows = asArray(partners);
    const { auth } = usePage<PageProps>().props;
    const isSuperAdmin = auth.role === 'super_admin';
    const canCreate = isSuperAdmin || (auth.permissions.includes('partners.create') && ['admin', 'super_admin'].includes(auth.role ?? ''));
    const canEdit = isSuperAdmin || (auth.permissions.includes('partners.edit') && ['admin', 'super_admin'].includes(auth.role ?? ''));
    const canDelete = isSuperAdmin || (auth.permissions.includes('partners.delete') && ['admin', 'super_admin'].includes(auth.role ?? ''));
    const columnHelper = createColumnHelper<LooseRecord>();
    const columns = [
        columnHelper.accessor((row) => String(row.name ?? '-'), { id: 'name', header: 'Partner' }),
        columnHelper.accessor((row) => String(row.email ?? '-'), { id: 'email', header: 'Email' }),
        columnHelper.accessor((row) => Number(row.customers_count ?? 0), {
            id: 'customers_count',
            header: 'Customers',
            cell: (info) => (
                <span>{info.getValue()}</span>
            )
        }),
        columnHelper.accessor((row) => String(row.api_key_status ?? 'inactive'), {
            id: 'api_key_status',
            header: 'API Status',
            cell: (info) => {
                const status = info.getValue() as string;
                return (
                    <span
                        className={`rounded-full px-2.5 py-1 text-xs font-medium ${
                            status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'
                        }`}
                    >
                        {status.toUpperCase()}
                    </span>
                );
            }
        }),
        columnHelper.accessor((row) => String(row.status ?? 'inactive'), {
            id: 'status',
            header: 'Status',
            cell: (info) => {
                const status = info.getValue() as string;
                const id = Number(info.row.original.id ?? 0);
                const isActive = status === 'active';

                return (
                    <div className="flex items-center gap-3">
                        {/* Toggle Switch */}
                        <label className="relative inline-flex cursor-pointer items-center">
                            <input
                                type="checkbox"
                                className="peer sr-only"
                                checked={isActive}
                                disabled={!canEdit}
                                onChange={() => {
                                    router.post(
                                        route('admin.partners.toggle-status', id),
                                        {},
                                        {
                                            preserveScroll: true,
                                        },
                                    );
                                }}
                            />
                            <div className="h-6 w-11 rounded-full bg-gray-300 transition peer-checked:bg-green-500"></div>
                            <div className="absolute top-1 left-1 h-4 w-4 rounded-full bg-white transition peer-checked:translate-x-5"></div>
                        </label>

                        {/* Status Badge */}
                        <span
                            className={`rounded-full px-2.5 py-1 text-xs font-medium ${
                                isActive ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600'
                            }`}
                        >
                            {isActive ? 'Active' : 'Inactive'}
                        </span>
                    </div>
                );
            },
        }),
        columnHelper.display({
            id: 'actions',
            header: 'Actions',
            cell: (info) => {
                const id = Number(info.row.original.id ?? 0);
                return (
                    <div className="flex items-center justify-center gap-2">
                        <button
                            className="text-primary hover:bg-accent/70 inline-flex items-center rounded-md p-1.5 transition-colors"
                            onClick={() => router.visit(route('admin.partners.show', id))}
                            aria-label="View partner"
                            title="View"
                        >
                            <Eye className="h-3.5 w-3.5" />
                        </button>
                        <button
                            className="text-[#0e9f84] hover:underline disabled:cursor-not-allowed disabled:opacity-50"
                            onClick={() => router.visit(route('admin.partners.edit', id))}
                            disabled={!canEdit}
                        >
                            Edit
                        </button>
                        <button
                            className="text-red-600 hover:underline disabled:cursor-not-allowed disabled:opacity-50"
                            onClick={() => {
                                const confirmed = confirm('Are you sure you want to delete this partner? This action cannot be undone.');
                                if (!confirmed) return;
                                router.delete(route('admin.partners.destroy', id), {
                                    preserveScroll: true,
                                });
                            }}
                            disabled={!canDelete}
                        >
                            Delete
                        </button>
                    </div>
                );
            },
        }),
    ];

    return (
        <AdminLayout title="Partners">
            <div className="mb-4 flex justify-end">
                <Button
                    className="bg-[#0e9f84] text-white hover:bg-[#0c8f77]"
                    onClick={() => router.visit(route('admin.partners.create'))}
                    disabled={!canCreate}
                >
                    Create Partner
                </Button>
            </div>
            <DataTable columns={columns} data={rows} stripedRows showRowCount emptyMessage="No partners yet." stickyHeader compact />
        </AdminLayout>
    );
}
