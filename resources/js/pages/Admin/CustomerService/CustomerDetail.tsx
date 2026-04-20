import AdminLayout from '@/layouts/AdminLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Lock } from 'lucide-react';

type LooseRecord = Record<string, unknown>;

function asRecord(input: unknown): LooseRecord {
    if (input && typeof input === 'object') return input as LooseRecord;
    return {};
}

function fmt(value: unknown): string {
    if (value === null || value === undefined || value === '') return '—';
    return String(value);
}

function InfoRow({ label, value, hidden }: { label: string; value: unknown; hidden?: boolean }) {
    return (
        <div className="flex items-center justify-between border-b border-border/60 py-2 last:border-none">
            <span className="text-sm font-medium text-muted-foreground">{label}</span>
            {hidden ? (
                <span className="flex items-center gap-1 text-sm text-muted-foreground/60">
                    <Lock className="h-3.5 w-3.5" />
                    Restricted
                </span>
            ) : (
                <span className="max-w-[60%] truncate text-sm text-foreground">{fmt(value)}</span>
            )}
        </div>
    );
}

export default function CustomerDetail({
    customer,
    payment_history,
    can_view_payment_amount,
}: {
    customer: unknown;
    payment_history?: unknown[];
    can_view_payment_amount?: boolean;
}) {
    const model = asRecord(customer);
    const payments = Array.isArray(payment_history) ? payment_history : [];
    const submitted = asRecord(model.submitted_data);
    const partner = asRecord(model.partner);
    const product = asRecord(model.product);

    const latestPayment = payments.length > 0 ? asRecord(payments[0]) : null;

    return (
        <AdminLayout title="Customer detail">
            {/* BRD CS-001: Summary cards */}
            <div className="mb-4 grid gap-4 md:grid-cols-3">
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-base">Customer</CardTitle>
                    </CardHeader>
                    <CardContent className="text-sm font-medium text-foreground">
                        {fmt(model.full_name ?? `${model.first_name ?? ''} ${model.last_name ?? ''}`)}
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-base">Status</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <span
                            className={`rounded-full px-2.5 py-1 text-xs font-medium ${
                                model.status === 'active'
                                    ? 'bg-emerald-100 text-emerald-700'
                                    : model.status === 'expired'
                                      ? 'bg-amber-100 text-amber-700'
                                      : 'bg-red-100 text-red-700'
                            }`}
                        >
                            {fmt(model.status).toUpperCase()}
                        </span>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-base">Total payments</CardTitle>
                    </CardHeader>
                    <CardContent className="text-sm font-medium text-foreground">{payments.length}</CardContent>
                </Card>
            </div>

            <div className="grid gap-4 lg:grid-cols-2">
                {/* BRD Section 3.4: Customer detail fields */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Customer information</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <InfoRow label="Customer ID" value={model.uuid} />
                        <InfoRow label="Full name" value={model.full_name ?? `${model.first_name ?? ''} ${model.last_name ?? ''}`} />
                        <InfoRow label="Email" value={model.email} />
                        <InfoRow label="Phone" value={model.phone} />
                        <InfoRow label="Partner" value={partner.name} />
                        <InfoRow label="Product" value={product.name} />
                        <InfoRow label="Cover start date" value={model.cover_start_date} />
                        <InfoRow label="Cover end date" value={model.cover_end_date} />
                        <InfoRow label="Cover duration (months)" value={model.cover_duration_months} />
                        <InfoRow label="Customer since" value={model.customer_since} />
                        <InfoRow label="Last payment date" value={latestPayment?.payment_date} />
                        {/* BRD: Payment amount hidden for CS role */}
                        <InfoRow label="Payment amount" value={latestPayment?.amount} hidden={!can_view_payment_amount} />
                    </CardContent>
                </Card>

                {/* BRD: Submitted data fields */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Submitted data</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {Object.keys(submitted).length === 0 ? (
                            <p className="text-sm text-muted-foreground">No submitted data.</p>
                        ) : (
                            Object.entries(submitted).map(([key, value]) => (
                                <InfoRow key={key} label={key} value={value} />
                            ))
                        )}
                    </CardContent>
                </Card>

                {/* BRD SA-003: Payment history (full for super admin) */}
                <Card className="lg:col-span-2">
                    <CardHeader>
                        <CardTitle className="text-base">Payment history</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {payments.length === 0 ? (
                            <p className="text-sm text-muted-foreground">No payment records.</p>
                        ) : (
                            <div className="overflow-auto">
                                <table className="w-full min-w-[600px] text-sm">
                                    <thead>
                                        <tr className="border-b border-border text-muted-foreground">
                                            <th className="py-2 text-left font-medium">Transaction Ref</th>
                                            <th className="py-2 text-left font-medium">Date</th>
                                            <th className="py-2 text-left font-medium">Status</th>
                                            <th className="py-2 text-left font-medium">Currency</th>
                                            <th className="py-2 text-right font-medium">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {payments.map((item, idx) => {
                                            const row = asRecord(item);
                                            return (
                                                <tr key={String(row.uuid ?? idx)} className="border-b border-border/60">
                                                    <td className="py-2 font-mono text-xs text-foreground">{fmt(row.transaction_reference)}</td>
                                                    <td className="py-2 text-foreground">{fmt(row.payment_date)}</td>
                                                    <td className="py-2">
                                                        <span className="rounded-full bg-emerald-100 px-2 py-0.5 text-xs text-emerald-700">
                                                            {fmt(row.payment_status)}
                                                        </span>
                                                    </td>
                                                    <td className="py-2 text-foreground">{fmt(row.currency)}</td>
                                                    <td className="py-2 text-right">
                                                        {row.restricted ? (
                                                            <span className="flex items-center justify-end gap-1 text-muted-foreground/60">
                                                                <Lock className="h-3 w-3" />
                                                                Hidden
                                                            </span>
                                                        ) : (
                                                            <span className="font-semibold text-emerald-600">
                                                                {row.amount != null
                                                                    ? new Intl.NumberFormat('en-US', { style: 'currency', currency: String(row.currency ?? 'USD') }).format(Number(row.amount))
                                                                    : '—'}
                                                            </span>
                                                        )}
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AdminLayout>
    );
}
