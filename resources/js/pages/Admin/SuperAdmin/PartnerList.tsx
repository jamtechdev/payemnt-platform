import AdminLayout from '@/layouts/AdminLayout';
import { PageProps } from '@/Types';
import DataTable from '@/components/shared/DataTable';
import { Button } from '@/components/ui/button';
import { router, usePage } from '@inertiajs/react';
import { createColumnHelper } from '@tanstack/react-table';
import { Eye, RotateCcw } from 'lucide-react';
import { useState } from 'react';

type LooseRecord = Record<string, unknown>;

function asArray(input: unknown): LooseRecord[] {
    if (Array.isArray(input)) return input as LooseRecord[];
    if (input && typeof input === 'object' && Array.isArray((input as { data?: unknown }).data)) {
        return (input as { data: LooseRecord[] }).data;
    }
    return [];
}

export default function PartnerList({ partners, deletedPartners }: { partners: unknown; deletedPartners?: LooseRecord[] }) {
    const { auth, flash } = usePage<PageProps>().props;
    const [showDeleted, setShowDeleted] = useState(false);
    const isSuperAdmin = auth.role === 'super_admin';
    const canCreate = isSuperAdmin || auth.permissions.includes('partners.create');
    const canEdit   = isSuperAdmin || auth.permissions.includes('partners.edit');
    const canDelete = isSuperAdmin || auth.permissions.includes('partners.delete');

    const rows = asArray(partners);
    const deleted = Array.isArray(deletedPartners) ? deletedPartners : [];

    const columnHelper = createColumnHelper<LooseRecord>();

    const columns = [
        columnHelper.accessor((row) => String(row.name ?? '-'), { id: 'name', header: 'Partner' }),
        columnHelper.accessor((row) => String(row.partner_code ?? '-'), { id: 'partner_code', header: 'Partner Code' }),
        columnHelper.accessor((row) => String(row.contact_email ?? '-'), { id: 'contact_email', header: 'Email' }),
        columnHelper.accessor((row) => Number(row.customers_count ?? 0), { id: 'customers_count', header: 'Customers' }),
        columnHelper.accessor((row) => String(row.api_key_status ?? 'inactive'), {
            id: 'api_key_status',
            header: 'API Status',
            cell: (info) => {
                const status = info.getValue();
                return (
                    <span className={`rounded-full px-2.5 py-1 text-xs font-medium ${status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'}`}>
                        {status.toUpperCase()}
                    </span>
                );
            },
        }),
        columnHelper.accessor((row) => String(row.status ?? 'inactive'), {
            id: 'status',
            header: 'Status',
            cell: (info) => {
                const status = info.getValue();
                const id = Number(info.row.original.id ?? 0);
                const isActive = status === 'active';
                return (
                    <div className="flex items-center gap-3">
                        <label className="relative inline-flex cursor-pointer items-center">
                            <input
                                type="checkbox"
                                className="peer sr-only"
                                checked={isActive}
                                disabled={!canEdit}
                                onChange={() => router.post(route('admin.partners.toggle-status', id), {}, { preserveScroll: true })}
                            />
                            <div className="h-6 w-11 rounded-full bg-gray-300 transition peer-checked:bg-green-500"></div>
                            <div className="absolute top-1 left-1 h-4 w-4 rounded-full bg-white transition peer-checked:translate-x-5"></div>
                        </label>
                        <span className={`rounded-full px-2.5 py-1 text-xs font-medium ${isActive ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600'}`}>
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
                    <div className="flex items-center gap-2">
                        <button
                            className="text-primary hover:bg-accent/70 inline-flex items-center rounded-md p-1.5 transition-colors"
                            onClick={() => router.visit(route('admin.partners.show', id))}
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
                            disabled={!canDelete}
                            onClick={() => {
                                if (confirm('Delete this partner? You can restore it later from the deleted list.')) {
                                    router.delete(route('admin.partners.destroy', id), { preserveScroll: true });
                                }
                            }}
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
            {/* Flash messages */}
            {(flash as any)?.success && (
                <div className="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-700 dark:bg-green-900/20 dark:text-green-300">
                    ✅ {(flash as any).success}
                </div>
            )}
            {(flash as any)?.error && (
                <div className="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-700 dark:bg-red-900/20 dark:text-red-300">
                    ❌ {(flash as any).error}
                </div>
            )}

            <div className="mb-4 flex items-center justify-between">
                <div className="flex items-center gap-2">
                    {deleted.length > 0 && (
                        <button
                            className="text-sm text-gray-500 underline hover:text-gray-700"
                            onClick={() => setShowDeleted(!showDeleted)}
                        >
                            {showDeleted ? 'Hide' : `Show deleted (${deleted.length})`}
                        </button>
                    )}
                </div>
                <Button
                    className="bg-[#0e9f84] text-white hover:bg-[#0c8f77]"
                    onClick={() => router.visit(route('admin.partners.create'))}
                    disabled={!canCreate}
                >
                    Create Partner
                </Button>
            </div>

            <DataTable columns={columns} data={rows} stripedRows showRowCount emptyMessage="No partners yet." stickyHeader compact />

            {/* Deleted Partners Section */}
            {showDeleted && deleted.length > 0 && (
                <div className="mt-8">
                    <h3 className="mb-3 text-sm font-semibold text-gray-600 dark:text-gray-400">Deleted Partners</h3>
                    <div className="overflow-hidden rounded-xl border border-red-100 dark:border-red-900/30">
                        <table className="w-full text-sm">
                            <thead className="bg-red-50 dark:bg-red-900/20">
                                <tr>
                                    <th className="px-4 py-2 text-left font-medium text-gray-600">Name</th>
                                    <th className="px-4 py-2 text-left font-medium text-gray-600">Partner Code</th>
                                    <th className="px-4 py-2 text-left font-medium text-gray-600">Email</th>
                                    <th className="px-4 py-2 text-left font-medium text-gray-600">Deleted On</th>
                                    <th className="px-4 py-2 text-left font-medium text-gray-600">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                {deleted.map((p) => (
                                    <tr key={String(p.id)} className="border-t border-red-100 dark:border-red-900/20">
                                        <td className="px-4 py-2 text-gray-700 dark:text-gray-300">{String(p.name ?? '-')}</td>
                                        <td className="px-4 py-2 font-mono text-gray-600 dark:text-gray-400">{String(p.partner_code ?? '-')}</td>
                                        <td className="px-4 py-2 text-gray-600 dark:text-gray-400">{String(p.contact_email ?? '-')}</td>
                                        <td className="px-4 py-2 text-gray-500">{String(p.deleted_at ?? '-')}</td>
                                        <td className="px-4 py-2">
                                            {canDelete && (
                                                <button
                                                    className="inline-flex items-center gap-1 rounded-md bg-green-50 px-2.5 py-1 text-xs font-medium text-green-700 hover:bg-green-100"
                                                    onClick={() => {
                                                        if (confirm(`Restore "${p.name}"?`)) {
                                                            router.post(route('admin.partners.restore', p.id), {}, { preserveScroll: true });
                                                        }
                                                    }}
                                                >
                                                    <RotateCcw className="h-3 w-3" /> Restore
                                                </button>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}
