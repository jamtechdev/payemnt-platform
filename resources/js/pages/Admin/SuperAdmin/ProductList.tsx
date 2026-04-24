import DataTable from '@/components/shared/DataTable';
import StatusBadge from '@/components/shared/StatusBadge';
import { PageProps } from '@/Types';
import AdminLayout from '@/layouts/AdminLayout';
import { usePage } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import { createColumnHelper } from '@tanstack/react-table';
import { Link } from '@inertiajs/react';
import { Pencil } from 'lucide-react';

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
    const { auth } = usePage<PageProps>().props;
    const isSuperAdmin = auth.role === 'super_admin';
    const columnHelper = createColumnHelper<LooseRecord>();
    const columns: ColumnDef<LooseRecord, any>[] = [
        columnHelper.accessor((row) => String(row.name ?? '-'), { id: 'name', header: 'Name' }),
        columnHelper.display({
            id: 'image',
            header: 'Image',
            cell: (info) => {
                const image = info.row.original.image as string | null;
                if (!image) return <span className="text-xs text-muted-foreground">No image</span>;
                const url = image.startsWith('http') ? image : `/storage/${image}`;
                return <img src={url} alt="product" className="h-10 w-10 rounded-md object-cover border border-slate-200" />;
            },
        }),
        columnHelper.display({
            id: 'partner',
            header: 'Partner',
            cell: (info) => {
                const row = info.row.original;
                // Direct partner_id se (API created products)
                const direct = row.partner_direct as LooseRecord | null;
                if (direct?.name) return <span className="text-xs">{String(direct.name)}</span>;
                // Pivot relation se (admin created products)
                const pivotPartners = Array.isArray(row.partners) ? (row.partners as LooseRecord[]) : [];
                if (pivotPartners.length > 0) return <span className="text-xs">{pivotPartners.map((p) => String(p.name ?? '-')).join(', ')}</span>;
                return <span className="text-xs text-muted-foreground">-</span>;
            },
        }),
        columnHelper.accessor((row) => String(row.price ?? '-'), { id: 'price', header: 'Price' }),
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
                        <Link
                            className="inline-flex items-center rounded-md p-1.5 text-primary transition-colors hover:bg-accent/70"
                            href={route('admin.products.edit', id)}
                            aria-label="Edit product"
                            title="Edit"
                        >
                            <Pencil className="h-3.5 w-3.5" />
                        </Link>
                    </div>
                );
            },
        }),
    ];

    return (
        <AdminLayout title="Products">
            <DataTable columns={columns} data={rows} stripedRows showRowCount clickableRows={false} emptyMessage="No products found." stickyHeader compact />
        </AdminLayout>
    );
}
