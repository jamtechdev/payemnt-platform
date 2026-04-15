import EntityListCard from '@/components/admin/EntityListCard';
import AdminLayout from '@/layouts/AdminLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

type LooseRecord = Record<string, unknown>;

function asRecord(input: unknown): LooseRecord {
    if (input && typeof input === 'object') {
        return input as LooseRecord;
    }
    return {};
}

export default function CustomerDetail({ customer, payment_history }: { customer: unknown; payment_history?: unknown[] }) {
    const model = asRecord(customer);
    const payments = Array.isArray(payment_history) ? payment_history : [];
    const submitted = asRecord(model.submitted_data);

    return (
        <AdminLayout title="Customer detail">
            <div className="mb-4 grid gap-4 md:grid-cols-3">
                <Card>
                    <CardHeader className="pb-2"><CardTitle className="text-base">Customer</CardTitle></CardHeader>
                    <CardContent className="text-sm text-slate-700">{String(model.full_name ?? `${model.first_name ?? ''} ${model.last_name ?? ''}`).trim()}</CardContent>
                </Card>
                <Card>
                    <CardHeader className="pb-2"><CardTitle className="text-base">Status</CardTitle></CardHeader>
                    <CardContent className="text-sm text-slate-700">{String(model.status ?? '-')}</CardContent>
                </Card>
                <Card>
                    <CardHeader className="pb-2"><CardTitle className="text-base">Payments</CardTitle></CardHeader>
                    <CardContent className="text-sm text-slate-700">{payments.length}</CardContent>
                </Card>
            </div>

            <div className="grid gap-4 lg:grid-cols-2">
                <EntityListCard
                    title="Customer information"
                    emptyText="No customer data."
                    items={[
                        ['customer_id', model.uuid],
                        ['email', model.email],
                        ['phone', model.phone],
                        ['cover_start_date', model.cover_start_date],
                        ['cover_end_date', model.cover_end_date],
                        ['cover_duration_months', model.cover_duration_months],
                        ['customer_since', model.customer_since],
                        ['partner', asRecord(model.partner).name],
                        ['product', asRecord(model.product).name],
                    ].map(([key, value]) => ({
                        key,
                        content: (
                            <div className="flex items-center justify-between gap-2">
                                <span className="text-sm font-medium text-slate-700">{key}</span>
                                <span className="max-w-[65%] truncate text-sm text-slate-600">{typeof value === 'string' ? value : JSON.stringify(value)}</span>
                            </div>
                        ),
                    }))}
                />
                <EntityListCard
                    title="Submitted data"
                    emptyText="No submitted data."
                    items={Object.entries(submitted).map(([key, value]) => ({
                        key,
                        content: (
                            <div className="flex items-center justify-between gap-2">
                                <span className="text-sm font-medium text-slate-700">{key}</span>
                                <span className="max-w-[65%] truncate text-sm text-slate-600">{typeof value === 'string' ? value : JSON.stringify(value)}</span>
                            </div>
                        ),
                    }))}
                />
                <EntityListCard
                    title="Payment history"
                    emptyText="No payment records."
                    items={payments.map((item, idx) => {
                        const row = asRecord(item);
                        return {
                            key: String(row.uuid ?? idx),
                            content: (
                                <div className="space-y-1 text-sm">
                                    <p className="font-medium text-slate-800">{String(row.transaction_reference ?? 'Transaction')}</p>
                                    <p className="text-slate-600">{String(row.payment_date ?? '-')}</p>
                                </div>
                            ),
                        };
                    })}
                />
            </div>
        </AdminLayout>
    );
}
