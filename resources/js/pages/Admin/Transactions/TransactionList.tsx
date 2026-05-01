import AdminLayout from '@/layouts/AdminLayout';
import DataTable from '@/components/shared/DataTable';
import StatusBadge from '@/components/shared/StatusBadge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { createColumnHelper } from '@tanstack/react-table';
import { Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { Eye, Search } from 'lucide-react';
import ActionBtn from '@/components/shared/ActionBtn';

interface TransactionRow {
    id: number;
    transaction_number?: string;
    status?: string;
    payment_message?: string;
    cover_duration?: string;
    cover_start_date?: string;
    cover_end_date?: string;
    stripe_payment_intent?: string;
    stripe_payment_status?: string;
    paid_at?: string;
    customer?: { first_name?: string; last_name?: string; email?: string };
    partner?: { name?: string };
    product?: { name?: string; product_code?: string };
}

interface PaginatedTransactions {
    data: TransactionRow[];
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

export default function TransactionList({ transactions, filters }: { transactions: PaginatedTransactions; filters?: Filters }) {
    const rows = transactions?.data ?? [];
    const [form, setForm] = useState<Filters>(filters ?? {});

    const applyFilters = () => {
        router.get(route('admin.transactions.index'), form as Record<string, string>, { preserveState: true });
    };

    const columnHelper = createColumnHelper<TransactionRow>();

    useEffect(() => {
        const intervalId = window.setInterval(() => {
            router.reload({
                only: ['transactions'],
                preserveScroll: true,
                preserveState: true,
            });
        }, 7000);

        return () => window.clearInterval(intervalId);
    }, []);
    const columns = [
        columnHelper.accessor((row) => row.transaction_number ?? '-', {
            id: 'transaction_number',
            header: 'Transaction #',
            cell: (info) => <span className="font-mono text-xs">{info.getValue()}</span>,
        }),
        columnHelper.accessor((row) => `${row.customer?.first_name ?? ''} ${row.customer?.last_name ?? ''}`.trim() || '-', {
            id: 'customer',
            header: 'Customer',
        }),
        columnHelper.accessor((row) => row.customer?.email ?? '-', { id: 'email', header: 'Email' }),
        columnHelper.accessor((row) => row.partner?.name ?? '-', { id: 'partner', header: 'Partner' }),
        columnHelper.accessor((row) => row.product?.product_code ?? '-', { id: 'product_code', header: 'Product Code' }),
        columnHelper.accessor((row) => row.cover_duration ?? '-', { id: 'cover_duration', header: 'Cover Duration' }),
        columnHelper.accessor((row) => row.paid_at ?? '-', {
            id: 'paid_at',
            header: 'Date Added',
            cell: (info) => {
                const v = info.getValue();
                if (!v || v === '-') return '-';
                const d = new Date(v);
                return isNaN(d.getTime()) ? v : d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
            },
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
                <ActionBtn tone="primary" href={route('admin.transactions.show', info.row.original.id)} title="View">
                    <Eye className="h-3.5 w-3.5" /> View
                </ActionBtn>
            ),
        }),
    ];

    return (
        <AdminLayout title="Transactions">
            <Card className="mb-4">
                <CardContent className="pt-4">
                    <div className="grid gap-3 md:grid-cols-4">
                        <div className="relative md:col-span-2">
                            <Search className="absolute top-2.5 left-3 h-4 w-4 text-muted-foreground" />
                            <input
                                type="text"
                                className="w-full rounded-md border border-input bg-background py-2 pr-3 pl-9 text-sm"
                                placeholder="Search transaction #, email..."
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
                            <option value="active">Active</option>
                            <option value="suspended">Suspended</option>
                            <option value="pending">Pending</option>
                        </select>
                        <Button onClick={applyFilters}>Search</Button>
                    </div>
                </CardContent>
            </Card>

            <div className="mb-4 grid gap-4 md:grid-cols-3">
                <Card className="border-blue-200/70 bg-blue-50/50">
                    <CardHeader className="pb-2"><CardTitle className="text-base">Total</CardTitle></CardHeader>
                    <CardContent className="text-2xl font-semibold">{rows.length}</CardContent>
                </Card>
                <Card className="border-emerald-200/70 bg-emerald-50/50">
                    <CardHeader className="pb-2"><CardTitle className="text-base">Active</CardTitle></CardHeader>
                    <CardContent className="text-2xl font-semibold text-emerald-600">
                        {rows.filter((r) => r.status?.toLowerCase() === 'active').length}
                    </CardContent>
                </Card>
                <Card className="border-red-200/70 bg-red-50/50">
                    <CardHeader className="pb-2"><CardTitle className="text-base">Suspended</CardTitle></CardHeader>
                    <CardContent className="text-2xl font-semibold text-red-600">
                        {rows.filter((r) => r.status?.toLowerCase() === 'suspended').length}
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader><CardTitle className="text-base">Transaction records</CardTitle></CardHeader>
                <CardContent>
                    <DataTable columns={columns} data={rows} showHeader showRowCount stripedRows clickableRows={false} emptyMessage="No transactions found." stickyHeader compact />
                    <div className="mt-4 flex items-center justify-between text-sm text-muted-foreground">
                        <span>Showing {transactions.from ?? 0}–{transactions.to ?? 0} of {transactions.total ?? 0}</span>
                        <div className="flex gap-1">
                            {transactions.links.map((link, i) => (
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
