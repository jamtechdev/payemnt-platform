import AdminLayout from '@/layouts/AdminLayout';
import DataTable from '@/components/shared/DataTable';
import StatusBadge from '@/components/shared/StatusBadge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { createColumnHelper } from '@tanstack/react-table';
import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { Eye, Search } from 'lucide-react';

interface SwapOfferRow {
    id: number;
    customer_email?: string;
    from_currency_code?: string;
    to_currency_code?: string;
    from_amount?: number;
    to_amount?: number;
    exchange_rate?: number;
    status?: string;
    date_added?: string;
    expiry_date_time?: string;
    partner?: { name?: string };
    customer?: { first_name?: string; last_name?: string };
}

interface PaginatedOffers {
    data: SwapOfferRow[];
    current_page: number;
    last_page: number;
    from: number;
    to: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

interface Filters {
    search?: string;
    status?: string;
}

function fmtDate(value: unknown): string {
    if (!value) return '—';
    const d = new Date(String(value));
    if (isNaN(d.getTime())) return String(value);
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

export default function SwapOfferList({ offers, filters }: { offers: PaginatedOffers; filters?: Filters }) {
    const rows = offers?.data ?? [];
    const [form, setForm] = useState<Filters>(filters ?? {});

    const applyFilters = () => {
        router.get(route('admin.swap-offers.index'), form as Record<string, string>, { preserveState: true });
    };

    const columnHelper = createColumnHelper<SwapOfferRow>();
    const columns = [
        columnHelper.accessor((row) => row.customer_email ?? '-', { id: 'customer_email', header: 'Customer Email' }),
        columnHelper.accessor((row) => row.partner?.name ?? '-', { id: 'partner', header: 'Partner' }),
        columnHelper.accessor((row) => row.from_currency_code ?? '-', { id: 'from_currency', header: 'From' }),
        columnHelper.accessor((row) => row.to_currency_code ?? '-', { id: 'to_currency', header: 'To' }),
        columnHelper.accessor((row) => row.from_amount ?? 0, {
            id: 'from_amount',
            header: 'From Amount',
            cell: (info) => <span className="font-mono text-xs">{Number(info.getValue()).toLocaleString()}</span>,
        }),
        columnHelper.accessor((row) => row.to_amount ?? 0, {
            id: 'to_amount',
            header: 'To Amount',
            cell: (info) => <span className="font-mono text-xs">{Number(info.getValue()).toLocaleString()}</span>,
        }),
        columnHelper.accessor((row) => row.date_added ?? '-', {
            id: 'date_added',
            header: 'Date Added',
            cell: (info) => fmtDate(info.getValue()),
        }),
        columnHelper.accessor((row) => row.status ?? '-', {
            id: 'status',
            header: 'Status',
            cell: (info) => <StatusBadge status={info.getValue()} type="customer" />,
        }),
        columnHelper.display({
            id: 'actions',
            header: 'Actions',
            cell: (info) => (
                <Link
                    className="inline-flex items-center gap-1 rounded-md px-3 py-1.5 text-sm text-primary transition-colors hover:bg-accent"
                    href={route('admin.swap-offers.show', info.row.original.id)}
                >
                    <Eye className="h-4 w-4" />
                    View
                </Link>
            ),
        }),
    ];

    return (
        <AdminLayout title="Swap Offers">
            <Card className="mb-4">
                <CardContent className="pt-4">
                    <div className="grid gap-3 md:grid-cols-4">
                        <div className="relative md:col-span-2">
                            <Search className="absolute top-2.5 left-3 h-4 w-4 text-muted-foreground" />
                            <input
                                type="text"
                                className="w-full rounded-md border border-input bg-background py-2 pr-3 pl-9 text-sm"
                                placeholder="Search email, currency..."
                                value={form.search ?? ''}
                                onChange={(e) => setForm({ ...form, search: e.target.value })}
                                onKeyDown={(e) => e.key === 'Enter' && applyFilters()}
                            />
                        </div>
                        <select
                            className="rounded-md border border-input bg-background px-3 py-2 text-sm"
                            value={form.status ?? ''}
                            onChange={(e) => setForm({ ...form, status: e.target.value })}
                        >
                            <option value="">All statuses</option>
                            <option value="Pending">Pending</option>
                            <option value="Completed">Completed</option>
                            <option value="Failed">Failed</option>
                            <option value="Expired">Expired</option>
                        </select>
                        <Button onClick={applyFilters}>Search</Button>
                    </div>
                </CardContent>
            </Card>

            <div className="mb-4 grid gap-4 md:grid-cols-3">
                <Card className="border-blue-200/70 bg-blue-50/50">
                    <CardHeader className="pb-2"><CardTitle className="text-base">Total</CardTitle></CardHeader>
                    <CardContent className="text-2xl font-semibold">{offers.total ?? 0}</CardContent>
                </Card>
                <Card className="border-amber-200/70 bg-amber-50/50">
                    <CardHeader className="pb-2"><CardTitle className="text-base">Pending</CardTitle></CardHeader>
                    <CardContent className="text-2xl font-semibold text-amber-600">
                        {rows.filter((r) => r.status?.toLowerCase() === 'pending').length}
                    </CardContent>
                </Card>
                <Card className="border-emerald-200/70 bg-emerald-50/50">
                    <CardHeader className="pb-2"><CardTitle className="text-base">Completed</CardTitle></CardHeader>
                    <CardContent className="text-2xl font-semibold text-emerald-600">
                        {rows.filter((r) => r.status?.toLowerCase() === 'completed').length}
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader><CardTitle className="text-base">Swap Offer records</CardTitle></CardHeader>
                <CardContent>
                    <DataTable columns={columns} data={rows} showHeader showRowCount stripedRows clickableRows={false} emptyMessage="No swap offers found." stickyHeader compact />
                    <div className="mt-4 flex items-center justify-between text-sm text-muted-foreground">
                        <span>Showing {offers.from ?? 0}–{offers.to ?? 0} of {offers.total ?? 0}</span>
                        <div className="flex gap-1">
                            {offers.links.map((link, i) => (
                                <button
                                    key={i}
                                    disabled={!link.url}
                                    onClick={() => link.url && router.get(link.url, {}, { preserveState: true })}
                                    className={`rounded px-3 py-1 text-xs border ${
                                        link.active
                                            ? 'bg-primary text-primary-foreground border-primary'
                                            : 'border-input hover:bg-accent disabled:opacity-40'
                                    }`}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ))}
                        </div>
                    </div>
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
