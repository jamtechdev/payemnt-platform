import AdminLayout from '@/layouts/AdminLayout';
import DataTable from '@/components/shared/DataTable';
import StatusBadge from '@/components/shared/StatusBadge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { createColumnHelper } from '@tanstack/react-table';
import { Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Download, Search } from 'lucide-react';
import { PageProps } from '@/Types';

interface CustomerRow {
    uuid: string;
    first_name: string;
    last_name: string;
    email: string;
    phone?: string | null;
    status: string;
    full_name?: string;
    partner?: { name?: string | null };
    product?: { name?: string | null };
    last_payment_date?: string | null;
    latest_payment_amount?: number | null;
    customer_since?: string | null;
    cover_end_date?: string | null;
}

interface PaginatedCustomers {
    data: CustomerRow[];
}

interface Filters {
    search?: string;
    partner_id?: string;
    product_id?: string;
    status?: string;
    date_from?: string;
    date_to?: string;
}

export default function CustomerList({ customers, filters }: { customers: PaginatedCustomers; filters?: Filters }) {
    const { auth } = usePage<PageProps>().props;
    const canExport = auth.permissions.includes('customers.export');
    const rows = customers?.data ?? [];
    const [form, setForm] = useState<Filters>(filters ?? {});
    const [exporting, setExporting] = useState(false);

    const applyFilters = () => {
        router.get(route('admin.customers.index'), form as Record<string, string>, { preserveState: true });
    };

    const handleExport = () => {
        setExporting(true);
        router.post(
            route('admin.customers.export'),
            form,
            {
                onFinish: () => setExporting(false),
            },
        );
    };

    const columnHelper = createColumnHelper<CustomerRow>();
    const columns = [
        columnHelper.accessor('uuid', {
            header: 'Customer ID',
            cell: (info) => (
                <Link className="font-mono text-xs text-primary hover:underline" href={route('admin.customers.show', info.row.original.uuid)}>
                    {String(info.getValue()).slice(0, 8)}
                </Link>
            ),
        }),
        columnHelper.accessor((row) => row.full_name ?? `${row.first_name ?? ''} ${row.last_name ?? ''}`.trim(), {
            id: 'full_name',
            header: 'Customer Name',
        }),
        columnHelper.accessor('email', { header: 'Email' }),
        columnHelper.accessor((row) => row.partner?.name ?? '-', { id: 'partner_name', header: 'Partner' }),
        columnHelper.accessor((row) => row.product?.name ?? '-', { id: 'product_name', header: 'Product' }),
        columnHelper.accessor((row) => row.customer_since ?? '-', { id: 'customer_since', header: 'Customer Since' }),
        columnHelper.accessor((row) => row.cover_end_date ?? '-', { id: 'cover_end_date', header: 'Cover End' }),
        columnHelper.accessor((row) => row.last_payment_date ?? '-', { id: 'last_payment_date', header: 'Last Payment' }),
        columnHelper.accessor('status', {
            header: 'Status',
            cell: (info) => <StatusBadge status={info.getValue()} type="customer" />,
        }),
    ];

    return (
        <AdminLayout title="Customers">
            {/* BRD CS-002/CS-003: Search & filter */}
            <Card className="mb-4">
                <CardContent className="pt-4">
                    <div className="grid gap-3 md:grid-cols-6">
                        <div className="relative md:col-span-2">
                            <Search className="absolute top-2.5 left-3 h-4 w-4 text-muted-foreground" />
                            <input
                                type="text"
                                className="w-full rounded-md border border-input bg-background py-2 pr-3 pl-9 text-sm"
                                placeholder="Search name, email, phone, ID..."
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
                            <option value="expired">Expired</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                        <input
                            type="date"
                            className="rounded-md border border-input bg-background px-3 py-2 text-sm"
                            value={form.date_from ?? ''}
                            onChange={(e) => setForm({ ...form, date_from: e.target.value })}
                            title="Customer since from"
                        />
                        <input
                            type="date"
                            className="rounded-md border border-input bg-background px-3 py-2 text-sm"
                            value={form.date_to ?? ''}
                            onChange={(e) => setForm({ ...form, date_to: e.target.value })}
                            title="Customer since to"
                        />
                        <div className="flex gap-2">
                            <Button className="flex-1" onClick={applyFilters}>
                                Search
                            </Button>
                            {/* BRD CS-005: Export CSV */}
                            {canExport && (
                                <Button variant="outline" onClick={handleExport} disabled={exporting}>
                                    <Download className="h-4 w-4" />
                                </Button>
                            )}
                        </div>
                    </div>
                </CardContent>
            </Card>

            <div className="mb-4 grid gap-4 md:grid-cols-3">
                <Card className="border-blue-200/70 bg-blue-50/50 dark:border-blue-500/25 dark:bg-blue-500/10">
                    <CardHeader className="pb-2">
                        <CardTitle className="text-base">Visible records</CardTitle>
                    </CardHeader>
                    <CardContent className="text-2xl font-semibold">{rows.length}</CardContent>
                </Card>
                <Card className="border-emerald-200/70 bg-emerald-50/50 dark:border-emerald-500/25 dark:bg-emerald-500/10">
                    <CardHeader className="pb-2">
                        <CardTitle className="text-base">Active</CardTitle>
                    </CardHeader>
                    <CardContent className="text-2xl font-semibold text-emerald-600">
                        {rows.filter((r) => r.status?.toLowerCase() === 'active').length}
                    </CardContent>
                </Card>
                <Card className="border-amber-200/70 bg-amber-50/50 dark:border-amber-500/25 dark:bg-amber-500/10">
                    <CardHeader className="pb-2">
                        <CardTitle className="text-base">Other status</CardTitle>
                    </CardHeader>
                    <CardContent className="text-2xl font-semibold text-amber-600">
                        {rows.filter((r) => r.status?.toLowerCase() !== 'active').length}
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle className="text-base">Customer records</CardTitle>
                </CardHeader>
                <CardContent>
                    <DataTable
                        columns={columns}
                        data={rows}
                        showHeader
                        showRowCount
                        stripedRows
                        clickableRows={false}
                        emptyMessage="No customer records found."
                        stickyHeader
                        compact
                    />
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
