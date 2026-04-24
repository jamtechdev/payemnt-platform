import AdminLayout from '@/layouts/AdminLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import StatusBadge from '@/components/shared/StatusBadge';
import { Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { ReactNode } from 'react';

type LooseRecord = Record<string, unknown>;

function fmtDate(value: unknown): string {
    if (!value) return '—';
    const d = new Date(String(value));
    if (isNaN(d.getTime())) return String(value);
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function fmt(value: unknown): string {
    if (value === null || value === undefined || value === '') return '—';
    return String(value);
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

export default function SwapOfferDetail({ offer }: { offer: unknown }) {
    const o = (offer && typeof offer === 'object' ? offer : {}) as LooseRecord;
    const customer = (o.customer && typeof o.customer === 'object' ? o.customer : {}) as LooseRecord;
    const partner  = (o.partner  && typeof o.partner  === 'object' ? o.partner  : {}) as LooseRecord;

    return (
        <AdminLayout title="Swap Offer Detail">
            <div className="mb-4">
                <Link href={route('admin.swap-offers.index')} className="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
                    <ArrowLeft className="h-4 w-4" /> Back to Swap Offers
                </Link>
            </div>

            <div className="grid gap-4 lg:grid-cols-2">
                <Card>
                    <CardHeader><CardTitle className="text-base">Swap Info</CardTitle></CardHeader>
                    <CardContent>
                        <InfoRow label="Status"             value={<StatusBadge status={fmt(o.status)} type="customer" />} />
                        <InfoRow label="From Currency"      value={fmt(o.from_currency_code)} />
                        <InfoRow label="To Currency"        value={fmt(o.to_currency_code)} />
                        <InfoRow label="From Amount"        value={Number(o.from_amount ?? 0).toLocaleString()} />
                        <InfoRow label="To Amount"          value={Number(o.to_amount ?? 0).toLocaleString()} />
                        <InfoRow label="Exchange Rate"      value={fmt(o.exchange_rate)} />
                        <InfoRow label="Base Amount"        value={Number(o.base_amount ?? 0).toLocaleString()} />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle className="text-base">Admin Share & Dates</CardTitle></CardHeader>
                    <CardContent>
                        <InfoRow label="Admin Share (%)"    value={fmt(o.admin_share)} />
                        <InfoRow label="Admin Share Amount" value={Number(o.admin_share_amount ?? 0).toLocaleString()} />
                        <InfoRow label="Expiry Date"        value={fmtDate(o.expiry_date_time)} />
                        <InfoRow label="Date Added"         value={fmtDate(o.date_added)} />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle className="text-base">Customer Info</CardTitle></CardHeader>
                    <CardContent>
                        <InfoRow label="Email" value={fmt(o.customer_email)} />
                        <InfoRow label="Name"  value={`${customer.first_name ?? ''} ${customer.last_name ?? ''}`.trim() || '—'} />
                        <InfoRow label="Phone" value={fmt(customer.phone)} />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle className="text-base">Partner Info</CardTitle></CardHeader>
                    <CardContent>
                        <InfoRow label="Partner" value={fmt(partner.name)} />
                    </CardContent>
                </Card>
            </div>
        </AdminLayout>
    );
}
