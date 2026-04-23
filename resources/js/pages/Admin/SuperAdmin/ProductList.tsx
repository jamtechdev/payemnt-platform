import DataTable from '@/components/shared/DataTable';
import StatusBadge from '@/components/shared/StatusBadge';
import { PageProps } from '@/Types';
import AdminLayout from '@/layouts/AdminLayout';
import { router, usePage } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import { createColumnHelper } from '@tanstack/react-table';
import { Layers } from 'lucide-react';
import { Link } from '@inertiajs/react';

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
    const canDelete = isSuperAdmin || (auth.permissions.includes('products.delete') && ['admin', 'super_admin'].includes(auth.role ?? ''));
    const columnHelper = createColumnHelper<LooseRecord>();
    const columns: ColumnDef<LooseRecord, any>[] = [
        columnHelper.accessor((row) => String(row.name ?? '-'), { id: 'name', header: 'Name' }),
        columnHelper.accessor((row) => String(row.slug ?? '-'), { id: 'slug', header: 'Slug' }),
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
            id: 'fields',
            header: 'Configured fields',
            cell: (info) => {
                const fields = Array.isArray(info.row.original.fields) ? (info.row.original.fields as LooseRecord[]) : [];
                if (fields.length === 0) return <span className="text-xs text-muted-foreground">No fields</span>;
                const labels = fields
                    .sort((a, b) => Number(a.sort_order ?? 0) - Number(b.sort_order ?? 0))
                    .slice(0, 3)
                    .map((field) => String(field.label ?? field.field_key ?? '-'));
                const remaining = fields.length - labels.length;
                return (
                    <div className="text-xs text-foreground">
                        <span>{labels.join(', ')}</span>
                        {remaining > 0 ? <span className="text-muted-foreground"> +{remaining} more</span> : null}
                    </div>
                );
            },
        }),
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
                        <Link className="inline-flex items-center rounded-md p-1.5 text-primary transition-colors hover:bg-accent/70" href={route('admin.products.versions', id)} aria-label="Product versions" title="Versions">
                            <Layers className="h-3.5 w-3.5" />
                        </Link>
                        <button
                            className="text-red-600 hover:underline disabled:cursor-not-allowed disabled:opacity-50"
                            onClick={() => {
                                if (confirm('Are you sure you want to delete this product?')) {
                                    router.delete(route('admin.products.destroy', id), {
                                        preserveScroll: true,
                                    });
                                }
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
        <AdminLayout title="Products">
            <DataTable columns={columns} data={rows} stripedRows showRowCount clickableRows={false} emptyMessage="No products found." stickyHeader compact />
        </AdminLayout>
    );
}
