import AdminLayout from '@/layouts/AdminLayout';
import DataTable from '@/components/shared/DataTable';
import StatusBadge from '@/components/shared/StatusBadge';
import { Button } from '@/components/ui/button';
import { router } from '@inertiajs/react';
import { createColumnHelper } from '@tanstack/react-table';

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
                        <button className="text-[#0e9f84] hover:underline" onClick={() => router.visit(route('admin.partners.show', id))}>
                            View
                        </button>
                        <button className="text-[#0e9f84] hover:underline" onClick={() => router.visit(route('admin.partners.edit', id))}>
                            Edit
                        </button>
                        <button className="text-red-600 hover:underline" onClick={() => router.delete(route('admin.partners.destroy', id))}>
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
                <Button className="bg-[#0e9f84] text-white hover:bg-[#0c8f77]" onClick={() => router.post(route('admin.partners.store'), { name: 'New Partner', email: `partner${Date.now()}@local.test`, phone: null })}>
                    Quick Create Partner
                </Button>
            </div>
            <DataTable columns={columns} data={rows} stripedRows showRowCount emptyMessage="No partners yet." />
        </AdminLayout>
    );
}
