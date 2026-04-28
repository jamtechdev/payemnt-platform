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
            <span className="max-w-[60%] text-sm text-foreground text-right">
                {typeof value === 'string' || typeof value === 'number' ? value : (value as ReactNode)}
            </span>
        </div>
    );
}

export default function ConnectArticleDetail({ article }: { article: unknown }) {
    const a = (article && typeof article === 'object' ? article : {}) as LooseRecord;

    return (
        <AdminLayout title="Connect Article Detail">
            <div className="mb-4">
                <Link href={route('admin.connect-articles.index')} className="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
                    <ArrowLeft className="h-4 w-4" /> Back to Connect Articles
                </Link>
            </div>

            <div className="grid gap-4 lg:grid-cols-2">
                <Card>
                    <CardHeader><CardTitle className="text-base">Article Info</CardTitle></CardHeader>
                    <CardContent>
                        <InfoRow label="Article Code"  value={fmt(a.article_code)} />
                        <InfoRow label="Category Code" value={fmt(a.category_code)} />
                        <InfoRow label="Title"         value={fmt(a.title)} />
                        <InfoRow label="Partner Code"  value={fmt(a.partner_code)} />
                        <InfoRow label="Status"        value={<StatusBadge status={fmt(a.status)} type="customer" />} />
                        <InfoRow label="From Platform" value={a.from_platform ? 'Yes' : 'No'} />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle className="text-base">Media & Dates</CardTitle></CardHeader>
                    <CardContent>
                        <InfoRow label="Image URL" value={a.image_url ? <a href={String(a.image_url)} target="_blank" rel="noopener noreferrer" className="text-primary hover:underline">View Image</a> : '—'} />
                        <InfoRow label="Created At" value={fmtDate(a.created_at)} />
                        <InfoRow label="Updated At" value={fmtDate(a.updated_at)} />
                    </CardContent>
                </Card>

                {a.description && (
                    <Card className="lg:col-span-2">
                        <CardHeader><CardTitle className="text-base">Description</CardTitle></CardHeader>
                        <CardContent>
                            <p className="text-sm text-foreground leading-relaxed whitespace-pre-wrap">{fmt(a.description)}</p>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AdminLayout>
    );
}
