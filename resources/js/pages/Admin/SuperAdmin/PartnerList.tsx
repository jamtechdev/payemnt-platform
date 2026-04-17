import AdminLayout from '@/layouts/AdminLayout';
import { PageProps } from '@/Types';
import DataTable from '@/components/shared/DataTable';
import StatusBadge from '@/components/shared/StatusBadge';
import { Button } from '@/components/ui/button';
import { router, usePage } from '@inertiajs/react';
import { createColumnHelper } from '@tanstack/react-table';
import { Eye, Pencil, Trash2 } from 'lucide-react';

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
    const canCreate = auth.permissions.includes('partners.create') && ['admin', 'super_admin'].includes(auth.role ?? '');
    const canEdit = auth.permissions.includes('partners.edit') && ['admin', 'super_admin'].includes(auth.role ?? '');
    const canDelete = auth.permissions.includes('partners.delete') && ['admin', 'super_admin'].includes(auth.role ?? '');
    const columnHelper = createColumnHelper<LooseRecord>();
    const columns = [
        columnHelper.accessor((row) => String(row.name ?? '-'), { id: 'name', header: 'Partner' }),
        columnHelper.accessor((row) => String(row.email ?? '-'), { id: 'email', header: 'Email' }),
        columnHelper.accessor((row) => String(row.status ?? 'inactive'), {
            id: 'status',
            header: 'Status',
            cell: (info) => <StatusBadge status={info.getValue()} type="partner" />,
        }),
        columnHelper.display({
            id: 'actions',
            header: 'Actions',
            cell: (info) => {
                const id = Number(info.row.original.id ?? 0);
                return (
                    <div className="flex items-center justify-center gap-2">
                        <button className="inline-flex items-center rounded-md p-1.5 text-primary transition-colors hover:bg-accent/70" onClick={() => router.visit(route('admin.partners.show', id))} aria-label="View partner" title="View">
                            <Eye className="h-3.5 w-3.5" />
                        </button>
                        <button className="inline-flex items-center rounded-md p-1.5 text-primary transition-colors hover:bg-accent/70 disabled:cursor-not-allowed disabled:opacity-50" onClick={() => router.visit(route('admin.partners.edit', id))} disabled={!canEdit} aria-label="Edit partner" title="Edit">
                            <Pencil className="h-3.5 w-3.5" />
                        </button>
                        <button className="inline-flex items-center rounded-md p-1.5 text-red-600 transition-colors hover:bg-red-500/10 disabled:cursor-not-allowed disabled:opacity-50" onClick={() => router.delete(route('admin.partners.destroy', id))} disabled={!canDelete} aria-label="Delete partner" title="Delete">
                            <Trash2 className="h-3.5 w-3.5" />
                        </button>
                    </div>
                );
            },
        }),
    ];

    return (
        <AdminLayout title="Partners">
            <div className="mb-4 flex justify-end">
                <Button onClick={() => router.post(route('admin.partners.store'), { name: 'New Partner', email: `partner${Date.now()}@local.test`, phone: null })} disabled={!canCreate}>
                    Quick Create Partner
                </Button>
            </div>
            <DataTable columns={columns} data={rows} stripedRows showRowCount emptyMessage="No partners yet." stickyHeader compact />
        </AdminLayout>
    );
}
