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
    return isNaN(d.getTime()) ? String(value) : d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function InfoRow({ label, value }: { label: string; value: unknown | ReactNode }) {
    return (
        <div className="flex items-center justify-between border-b border-border/60 py-2 last:border-none">
            <span className="text-sm font-medium text-muted-foreground">{label}</span>
            <span className="max-w-[60%] text-sm text-foreground text-right break-all">
                {typeof value === 'string' || typeof value === 'number' ? value : (value as ReactNode)}
            </span>
        </div>
    );
}

export default function RateApiDetail({ rateApi }: { rateApi: unknown }) {
    const r = (rateApi && typeof rateApi === 'object' ? rateApi : {}) as LooseRecord;

    return (
        <AdminLayout title="Rate API Detail">
            <div className="mb-4">
                <Link href={route('admin.rate-apis.index')} className="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
                    <ArrowLeft className="h-4 w-4" /> Back to Rate APIs
                </Link>
            </div>

            <div className="grid gap-4 lg:grid-cols-2">
                <Card>
                    <CardHeader><CardTitle className="text-base">Rate API Info</CardTitle></CardHeader>
                    <CardContent>
                        <InfoRow label="Name"          value={fmt(r.name)} />
                        <InfoRow label="Partner Code"  value={fmt(r.partner_code)} />
                        <InfoRow label="Status"        value={<StatusBadge status={fmt(r.status)} type="customer" />} />
                        <InfoRow label="From Platform" value={r.from_platform ? 'Yes' : 'No'} />
                        <InfoRow label="Created At"    value={fmtDate(r.created_at)} />
                        <InfoRow label="Updated At"    value={fmtDate(r.updated_at)} />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle className="text-base">API URL</CardTitle></CardHeader>
                    <CardContent>
                        <p className="text-sm text-foreground break-all font-mono leading-relaxed">
                            {fmt(r.url)}
                        </p>
                    </CardContent>
                </Card>
            </div>
        </AdminLayout>
    );
}
