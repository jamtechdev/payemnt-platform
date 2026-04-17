import AdminLayout from '@/layouts/AdminLayout';
import DataTable from '@/components/shared/DataTable';
import StatusBadge from '@/components/shared/StatusBadge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { createColumnHelper } from '@tanstack/react-table';
import { Link } from '@inertiajs/react';

interface CustomerRow {
    uuid: string;
    first_name: string;
    last_name: string;
    email: string;
    status: string;
    full_name?: string;
    partner?: { name?: string | null };
    product?: { name?: string | null };
    last_payment_date?: string | null;
    latest_payment_amount?: number | null;
}

interface PaginatedCustomers {
    data: CustomerRow[];
}

export default function CustomerList({ customers }: { customers: PaginatedCustomers }) {
    const rows = customers?.data ?? [];
    const columnHelper = createColumnHelper<CustomerRow>();
    const columns = [
        columnHelper.accessor('uuid', {
            header: 'Customer ID',
            cell: (info) => (
                <Link className="text-primary hover:underline" href={route('admin.customers.show', info.row.original.uuid)}>
                    {String(info.getValue()).slice(0, 8)}
                </Link>
            ),
        }),
        columnHelper.accessor((row) => row.full_name ?? `${row.first_name ?? ''} ${row.last_name ?? ''}`.trim(), { id: 'full_name', header: 'Customer Name' }),
        columnHelper.accessor('email', { header: 'Email' }),
        columnHelper.accessor((row) => row.partner?.name ?? '-', { id: 'partner_name', header: 'Partner' }),
        columnHelper.accessor((row) => row.product?.name ?? '-', { id: 'product_name', header: 'Product' }),
        columnHelper.accessor((row) => row.last_payment_date ?? '-', { id: 'last_payment_date', header: 'Last Payment' }),
        columnHelper.accessor('status', { header: 'Status', cell: (info) => <StatusBadge status={info.getValue()} type="customer" /> }),
    ];

    return (
        <AdminLayout title="Customers">
            <div className="mb-4 grid gap-4 md:grid-cols-3">
                <Card className="border-blue-200/70 bg-blue-50/50 dark:border-blue-500/25 dark:bg-blue-500/10"><CardHeader className="pb-2"><CardTitle className="text-base">Visible records</CardTitle></CardHeader><CardContent className="text-2xl font-semibold">{rows.length}</CardContent></Card>
                <Card className="border-emerald-200/70 bg-emerald-50/50 dark:border-emerald-500/25 dark:bg-emerald-500/10"><CardHeader className="pb-2"><CardTitle className="text-base">Active</CardTitle></CardHeader><CardContent className="text-2xl font-semibold text-emerald-600">{rows.filter((r) => r.status?.toLowerCase() === 'active').length}</CardContent></Card>
                <Card className="border-amber-200/70 bg-amber-50/50 dark:border-amber-500/25 dark:bg-amber-500/10"><CardHeader className="pb-2"><CardTitle className="text-base">Other status</CardTitle></CardHeader><CardContent className="text-2xl font-semibold text-amber-600">{rows.filter((r) => r.status?.toLowerCase() !== 'active').length}</CardContent></Card>
            </div>
            <Card>
                <CardHeader><CardTitle className="text-base">Customer records</CardTitle></CardHeader>
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
