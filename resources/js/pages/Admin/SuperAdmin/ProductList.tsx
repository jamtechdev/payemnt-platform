import DataTable from '@/components/shared/DataTable';
import StatusBadge from '@/components/shared/StatusBadge';
import AdminLayout from '@/layouts/AdminLayout';
import { Button } from '@/components/ui/button';
import { Link, router } from '@inertiajs/react';
import { createColumnHelper } from '@tanstack/react-table';

type LooseRecord = Record<string, unknown>;

function asArray(input: unknown): LooseRecord[] {
    if (Array.isArray(input)) return input as LooseRecord[];
    if (input && typeof input === 'object' && Array.isArray((input as { data?: unknown }).data)) {
        return (input as { data: LooseRecord[] }).data;
    }
    return [];
}

export default function ProductList({ products }: { products: unknown }) {
    const rows = asArray(products);
    const columnHelper = createColumnHelper<LooseRecord>();
    const columns = [
        columnHelper.accessor((row) => String(row.name ?? '-'), { id: 'name', header: 'Name' }),
        columnHelper.accessor((row) => String(row.slug ?? '-'), { id: 'slug', header: 'Slug' }),
        columnHelper.accessor((row) => String(row.status ?? 'inactive'), {
            id: 'status',
            header: 'Status',
            cell: (info) => <StatusBadge status={info.getValue()} type="product" />,
        }),
        columnHelper.display({
            id: 'actions',
            header: 'Actions',
            cell: (info) => {
                const row = info.row.original;
                const id = Number(row.id ?? 0);
                return (
                    <div className="flex items-center justify-center gap-2">
                        <Link className="text-[#0e9f84] hover:underline" href={route('admin.products.edit', id)}>
                            Edit
                        </Link>
                        <Link className="text-[#0e9f84] hover:underline" href={route('admin.products.versions', id)}>
                            Versions
                        </Link>
                        <button className="text-red-600 hover:underline" onClick={() => router.delete(route('admin.products.destroy', id))}>
                            Delete
                        </button>
                    </div>
                );
            },
        }),
    ];

    return (
        <AdminLayout title="Products">
            <div className="mb-4 flex justify-end">
                <Link href={route('admin.products.create')}>
                    <Button className="bg-[#0e9f84] text-white hover:bg-[#0c8f77]">Create Product</Button>
                </Link>
            </div>
            <DataTable columns={columns} data={rows} stripedRows showRowCount clickableRows={false} emptyMessage="No products found." />
        </AdminLayout>
    );
}
