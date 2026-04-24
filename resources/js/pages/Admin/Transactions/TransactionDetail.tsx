import AdminLayout from '@/layouts/AdminLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import StatusBadge from '@/components/shared/StatusBadge';
import { Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

import { ReactNode } from 'react';

type LooseRecord = Record<string, unknown>;

function fmt(value: unknown): string {
    if (value === null || value === undefined || value === '') return '—';
    return String(value);
}

function fmtDate(value: unknown): string {
    if (!value) return '—';
    const d = new Date(String(value));
    if (isNaN(d.getTime())) return String(value);
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function fmtDateTime(value: unknown): string {
    if (!value) return '—';
    const d = new Date(String(value));
    if (isNaN(d.getTime())) return String(value);
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function InfoRow({ label, value }: { label: string; value: unknown | ReactNode }) {
    return (
        <div className="flex items-center justify-between border-b border-border/60 py-2 last:border-none">
            <span className="text-sm font-medium text-muted-foreground">{label}</span>
            <span className="max-w-[60%] text-sm text-foreground">
                {typeof value === 'string' || typeof value === 'number' ? value : (value as ReactNode)}
            </span>
        </div>
    );
}

export default function TransactionDetail({ transaction }: { transaction: unknown }) {
    const t = (transaction && typeof transaction === 'object' ? transaction : {}) as LooseRecord;
    const customer = (t.customer && typeof t.customer === 'object' ? t.customer : {}) as LooseRecord;
    const partner  = (t.partner  && typeof t.partner  === 'object' ? t.partner  : {}) as LooseRecord;
    const product  = (t.product  && typeof t.product  === 'object' ? t.product  : {}) as LooseRecord;

    return (
        <AdminLayout title="Transaction Detail">
            <div className="mb-4">
                <Link href={route('admin.transactions.index')} className="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
                    <ArrowLeft className="h-4 w-4" /> Back to Transactions
                </Link>
            </div>

            <div className="grid gap-4 lg:grid-cols-2">
                <Card>
                    <CardHeader><CardTitle className="text-base">Transaction Info</CardTitle></CardHeader>
                    <CardContent>
                        <InfoRow label="Transaction #"       value={t.transaction_number} />
                        <InfoRow label="Status"              value={<StatusBadge status={String(t.status ?? '')} type="customer" />} />
                        <InfoRow label="Payment Message"     value={t.payment_message} />
                        <InfoRow label="Cover Duration"      value={t.cover_duration} />
                        <InfoRow label="Cover Start Date"    value={fmtDate(t.cover_start_date)} />
                        <InfoRow label="Cover End Date"      value={fmtDate(t.cover_end_date)} />
                        <InfoRow label="Date Added"          value={fmtDateTime(t.paid_at)} />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle className="text-base">Payment Info</CardTitle></CardHeader>
                    <CardContent>
                        <InfoRow label="Stripe Payment Intent" value={t.stripe_payment_intent} />
                        <InfoRow label="Stripe Payment Status" value={t.stripe_payment_status} />
                        <InfoRow label="Transaction Reference" value={t.transaction_reference} />
                        <InfoRow label="Amount"                value={t.amount} />
                        <InfoRow label="Currency"              value={t.currency} />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle className="text-base">Customer Info</CardTitle></CardHeader>
                    <CardContent>
                        <InfoRow label="Name"  value={`${customer.first_name ?? ''} ${customer.last_name ?? ''}`.trim()} />
                        <InfoRow label="Email" value={customer.email} />
                        <InfoRow label="Phone" value={customer.phone} />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle className="text-base">Partner & Product</CardTitle></CardHeader>
                    <CardContent>
                        <InfoRow label="Partner"      value={partner.name} />
                        <InfoRow label="Product"      value={product.name} />
                        <InfoRow label="Product Code" value={product.product_code} />
                        <InfoRow label="Product Type" value={t.product_type} />
                    </CardContent>
                </Card>
            </div>
        </AdminLayout>
    );
}
