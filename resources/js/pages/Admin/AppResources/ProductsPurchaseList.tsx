import AdminLayout from '@/layouts/AdminLayout';
import DataTable from '@/components/shared/DataTable';
import StatusBadge from '@/components/shared/StatusBadge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { createColumnHelper } from '@tanstack/react-table';
import { router } from '@inertiajs/react';
import { useState } from 'react';
import { Search } from 'lucide-react';

interface Row { id: number; customer_email: string; product_code: string; product_type: string; cover_duration: string; cover_start_date: string; cover_end_date: string; payment_status: string; transaction_number?: string; date_added?: string; partner?: { name?: string }; }
interface Paginated { data: Row[]; from: number; to: number; total: number; links: { url: string | null; label: string; active: boolean }[]; }

function fmtDate(v: unknown) {
    if (!v) return '—';
    const d = new Date(String(v));
    return isNaN(d.getTime()) ? String(v) : d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

export default function ProductsPurchaseList({ items, filters }: { items: Paginated; filters?: { search?: string } }) {
    const rows = items?.data ?? [];
    const [search, setSearch] = useState(filters?.search ?? '');
    const apply = () => router.get(route('admin.app-resources.products-purchases'), { search }, { preserveState: true });

    const col = createColumnHelper<Row>();
    const columns = [
        col.accessor((r) => r.partner?.name ?? '-', { id: 'partner', header: 'Partner' }),
        col.accessor('customer_email', { header: 'Customer Email' }),
        col.accessor('product_code', { header: 'Product Code' }),
        col.accessor('product_type', { header: 'Type' }),
        col.accessor('cover_duration', { header: 'Duration' }),
        col.accessor((r) => fmtDate(r.cover_start_date), { id: 'cover_start_date', header: 'Start Date' }),
        col.accessor((r) => fmtDate(r.cover_end_date), { id: 'cover_end_date', header: 'End Date' }),
        col.accessor('payment_status', { header: 'Status', cell: (info) => <StatusBadge status={info.getValue()} type="customer" /> }),
        col.accessor((r) => r.transaction_number ?? '-', { id: 'txn', header: 'Transaction #' }),
    ];

    return (
        <AdminLayout title="Products Purchases">
            <Card className="mb-4"><CardContent className="pt-4"><div className="flex gap-3">
                <div className="relative flex-1"><Search className="absolute top-2.5 left-3 h-4 w-4 text-muted-foreground" />
                    <input className="w-full rounded-md border border-input bg-background py-2 pr-3 pl-9 text-sm" placeholder="Search email, product, transaction..." value={search} onChange={(e) => setSearch(e.target.value)} onKeyDown={(e) => e.key === 'Enter' && apply()} />
                </div><Button onClick={apply}>Search</Button>
            </div></CardContent></Card>
            <Card><CardHeader><CardTitle className="text-base">Products Purchases</CardTitle></CardHeader>
                <CardContent>
                    <DataTable columns={columns} data={rows} showHeader showRowCount stripedRows clickableRows={false} emptyMessage="No purchases found." stickyHeader compact />
                    <div className="mt-4 flex items-center justify-between text-sm text-muted-foreground">
                        <span>Showing {items.from ?? 0}–{items.to ?? 0} of {items.total ?? 0}</span>
                        <div className="flex gap-1">{items.links.map((l, i) => <button key={i} disabled={!l.url} onClick={() => l.url && router.get(l.url, {}, { preserveState: true })} className={`rounded px-3 py-1 text-xs border ${l.active ? 'bg-primary text-primary-foreground border-primary' : 'border-input hover:bg-accent disabled:opacity-40'}`} dangerouslySetInnerHTML={{ __html: l.label }} />)}</div>
                    </div>
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
