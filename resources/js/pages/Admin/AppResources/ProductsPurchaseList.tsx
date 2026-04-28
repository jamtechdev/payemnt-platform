import AdminLayout from '@/layouts/AdminLayout';
import DataTable from '@/components/shared/DataTable';
import StatusBadge from '@/components/shared/StatusBadge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { createColumnHelper } from '@tanstack/react-table';
import { router } from '@inertiajs/react';
import { useState } from 'react';
import { Search } from 'lucide-react';

interface Row {
    id: number;
    swap_offers_requests_id: number;
    from_user_name: string;
    from_user_email: string;
    to_user_name: string;
    to_user_email: string;
    from_currency_name: string;
    from_currency_code: string;
    to_currency_name: string;
    to_currency_code: string;
    from_amount: number;
    to_amount: number;
    admin_share: number;
    admin_share_amount: number;
    base_amount: number;
    payment_method: string;
    status: string;
}
interface Paginated { data: Row[]; from: number; to: number; total: number; links: { url: string | null; label: string; active: boolean }[]; }

export default function ProductsPurchaseList({ items, filters }: { items: Paginated; filters?: { search?: string } }) {
    const rows = items?.data ?? [];
    const [search, setSearch] = useState(filters?.search ?? '');
    const apply = () => router.get(route('admin.app-resources.products-purchases'), { search }, { preserveState: true });

    const col = createColumnHelper<Row>();
    const columns = [
        col.accessor('from_user_name',  { header: 'From User' }),
        col.accessor('from_user_email', { header: 'From Email' }),
        col.accessor('to_user_name',    { header: 'To User' }),
        col.accessor('to_user_email',   { header: 'To Email' }),
        col.accessor((r) => `${r.from_currency_name} (${r.from_currency_code})`, { id: 'from_currency', header: 'From Currency' }),
        col.accessor((r) => `${r.to_currency_name} (${r.to_currency_code})`,     { id: 'to_currency',   header: 'To Currency' }),
        col.accessor((r) => Number(r.from_amount).toFixed(2),        { id: 'from_amount',        header: 'From Amount' }),
        col.accessor((r) => Number(r.to_amount).toFixed(2),          { id: 'to_amount',          header: 'To Amount' }),
        col.accessor((r) => `${Number(r.admin_share).toFixed(2)}%`,  { id: 'admin_share',        header: 'Admin Share' }),
        col.accessor((r) => Number(r.admin_share_amount).toFixed(2), { id: 'admin_share_amount', header: 'Admin Share Amt' }),
        col.accessor((r) => Number(r.base_amount).toFixed(2),        { id: 'base_amount',        header: 'Base Amount' }),
        col.accessor('payment_method', { header: 'Payment Method' }),
        col.accessor('status', { header: 'Status', cell: (info) => <StatusBadge status={info.getValue()} type="customer" /> }),
    ];

    return (
        <AdminLayout title="Products Purchases">
            <Card className="mb-4"><CardContent className="pt-4"><div className="flex gap-3">
                <div className="relative flex-1"><Search className="absolute top-2.5 left-3 h-4 w-4 text-muted-foreground" />
                    <input className="w-full rounded-md border border-input bg-background py-2 pr-3 pl-9 text-sm" placeholder="Search name, email, status..." value={search} onChange={(e) => setSearch(e.target.value)} onKeyDown={(e) => e.key === 'Enter' && apply()} />
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
