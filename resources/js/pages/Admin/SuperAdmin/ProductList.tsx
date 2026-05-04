import DataTable from '@/components/shared/DataTable';
import StatusBadge from '@/components/shared/StatusBadge';
import { PageProps } from '@/Types';
import AdminLayout from '@/layouts/AdminLayout';
import { usePage } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import { createColumnHelper } from '@tanstack/react-table';
import { Link } from '@inertiajs/react';
import { Pencil, Trash2, ToggleLeft, ToggleRight, Users } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { router } from '@inertiajs/react';
import ActionBtn from '@/components/shared/ActionBtn';

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
    const { auth, flash } = usePage<PageProps>().props;
    const flashAny = flash as any;
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
            header: 'Partners',
            cell: (info) => {
                const pivotPartners = Array.isArray(info.row.original.partners) ? (info.row.original.partners as LooseRecord[]) : [];
                if (pivotPartners.length === 0) return <span className="text-xs text-slate-400">—</span>;
                const visible = pivotPartners.slice(0, 2);
                const rest = pivotPartners.slice(2);
                return (
                    <div className="flex flex-wrap items-center gap-1">
                        {visible.map((p) => (
                            <span
                                key={String(p.id)}
                                className="inline-block max-w-[120px] truncate rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-200"
                                title={String(p.name ?? '')}
                            >
                                {String(p.name ?? '-')}
                            </span>
                        ))}
                        {rest.length > 0 && (
                            <span
                                className="inline-block cursor-default rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-500 ring-1 ring-inset ring-slate-200"
                                title={rest.map((p) => String(p.name)).join(', ')}
                            >
                                +{rest.length} more
                            </span>
                        )}
                    </div>
                );
            },
        }),
        columnHelper.accessor((row) => {
            return String(row.base_price ?? '-');
        }, { id: 'base_price', header: 'Base Price' }),
        columnHelper.accessor((row) => {
            if (!row.can_view_guide_price) return 'Hidden';
            return String(row.price ?? '-');
        }, { id: 'price', header: 'Guide Price' }),
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
                    <div className="flex items-center gap-1.5">
                        <ActionBtn tone="primary" href={route('admin.products.edit', id)} title="Edit">
                            <Pencil className="h-3.5 w-3.5" /> Edit
                        </ActionBtn>
                        <ActionBtn tone="muted" href={route('admin.products.assign-partners', id)} title="Assign Partners">
                            <Users className="h-3.5 w-3.5" /> Partners
                        </ActionBtn>
                        <ActionBtn
                            tone={row.status === 'active' ? 'success' : 'muted'}
                            title={row.status === 'active' ? 'Deactivate' : 'Activate'}
                            onClick={() => router.post(route('admin.products.toggle-status', id), {}, { preserveScroll: true })}
                        >
                            {row.status === 'active' ? <ToggleRight className="h-3.5 w-3.5" /> : <ToggleLeft className="h-3.5 w-3.5" />}
                            {row.status === 'active' ? 'Active' : 'Inactive'}
                        </ActionBtn>
                        <ActionBtn
                            tone="danger"
                            title="Delete"
                            onClick={() => { if (confirm('Delete this product?')) router.delete(route('admin.products.destroy', id), { preserveScroll: true }); }}
                        >
                            <Trash2 className="h-3.5 w-3.5" /> Delete
                        </ActionBtn>
                    </div>
                );
            },
        }),
    ];

    return (
        <AdminLayout title="Products">
            {flashAny?.success && (
                <div className="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    ✅ {flashAny.success}
                </div>
            )}
            {isSuperAdmin && (
                <div className="mb-4 flex justify-end">
                    <Link href={route('admin.products.create')}>
                        <Button>Create product</Button>
                    </Link>
                </div>
            )}
            <DataTable columns={columns} data={rows} stripedRows showRowCount clickableRows={false} emptyMessage="No products found." stickyHeader compact />
        </AdminLayout>
    );
}
