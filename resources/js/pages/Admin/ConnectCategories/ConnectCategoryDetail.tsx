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
            <span className="max-w-[60%] text-sm text-foreground">
                {typeof value === 'string' || typeof value === 'number' ? value : (value as ReactNode)}
            </span>
        </div>
    );
}

export default function ConnectCategoryDetail({ category }: { category: unknown }) {
    const c = (category && typeof category === 'object' ? category : {}) as LooseRecord;

    return (
        <AdminLayout title="Connect Category Detail">
            <div className="mb-4">
                <Link href={route('admin.connect-categories.index')} className="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
                    <ArrowLeft className="h-4 w-4" /> Back to Connect Categories
                </Link>
            </div>

            <div className="grid gap-4 lg:grid-cols-2">
                <Card>
                    <CardHeader><CardTitle className="text-base">Category Info</CardTitle></CardHeader>
                    <CardContent>
                        <InfoRow label="Category Code" value={fmt(c.category_code)} />
                        <InfoRow label="Name" value={fmt(c.name)} />
                        <InfoRow label="Partner Code" value={fmt(c.partner_code)} />
                        <InfoRow label="Status" value={<StatusBadge status={fmt(c.status)} type="customer" />} />
                        <InfoRow label="From Platform" value={c.from_platform ? 'Yes' : 'No'} />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle className="text-base">Additional Info</CardTitle></CardHeader>
                    <CardContent>
                        <InfoRow label="Icon URL" value={c.icon_url ? <a href={String(c.icon_url)} target="_blank" rel="noopener noreferrer" className="text-primary hover:underline">View Icon</a> : '—'} />
                        <InfoRow label="Created At" value={fmtDate(c.created_at)} />
                        <InfoRow label="Updated At" value={fmtDate(c.updated_at)} />
                    </CardContent>
                </Card>
            </div>
        </AdminLayout>
    );
}
