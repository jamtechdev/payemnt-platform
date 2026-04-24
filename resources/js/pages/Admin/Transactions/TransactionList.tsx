import AdminLayout from '@/layouts/AdminLayout';
import DataTable from '@/components/shared/DataTable';
import StatusBadge from '@/components/shared/StatusBadge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { createColumnHelper } from '@tanstack/react-table';
import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { Eye, Search } from 'lucide-react';

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
                <Link
                    className="inline-flex items-center gap-1 rounded-md px-3 py-1.5 text-sm text-primary transition-colors hover:bg-accent"
                    href={route('admin.transactions.show', info.row.original.id)}
                >
                    <Eye className="h-4 w-4" />
                    View
                </Link>
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
                            <option value="Successful">Successful</option>
                            <option value="Failed">Failed</option>
                            <option value="Pending">Pending</option>
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
                    <CardHeader className="pb-2"><CardTitle className="text-base">Successful</CardTitle></CardHeader>
                    <CardContent className="text-2xl font-semibold text-emerald-600">
                        {rows.filter((r) => r.status?.toLowerCase() === 'successful').length}
                    </CardContent>
                </Card>
                <Card className="border-red-200/70 bg-red-50/50">
                    <CardHeader className="pb-2"><CardTitle className="text-base">Failed</CardTitle></CardHeader>
                    <CardContent className="text-2xl font-semibold text-red-600">
                        {rows.filter((r) => r.status?.toLowerCase() === 'failed').length}
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader><CardTitle className="text-base">Transaction records</CardTitle></CardHeader>
                <CardContent>
                    <DataTable columns={columns} data={rows} showHeader showRowCount stripedRows clickableRows={false} emptyMessage="No transactions found." stickyHeader compact />
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
