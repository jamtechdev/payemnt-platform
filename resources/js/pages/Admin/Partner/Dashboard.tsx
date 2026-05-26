import AdminLayout from '@/layouts/AdminLayout';
import MetricCard from '@/components/admin/MetricCard';
import WelcomeBanner from '@/components/admin/WelcomeBanner';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';
import { Receipt, Key, Link2, Package, ArrowUpRight, BookOpen } from 'lucide-react';

interface PartnerInfo {
    id: number;
    name: string;
    partner_code: string;
    contact_email: string;
    status: string;
    connected_at: string | null;
    has_api_key: boolean;
}

interface Stats {
    total_customers: number;
    active_customers: number;
    total_transactions: number;
    monthly_transactions: number;
}

interface Transaction {
    id: number;
    transaction_number: string;
    customer_name: string | null;
    product_name: string | null;
    amount: number;
    currency: string;
    status: string;
    created_at: string;
}

interface Product {
    id: number;
    name: string;
    product_code: string;
}

interface PartnerDashboardProps {
    partner: PartnerInfo | null;
    stats: Stats;
    recentTransactions: Transaction[];
    products: Product[];
    currency: string;
}

export default function PartnerDashboard(props: PartnerDashboardProps) {
    if (!props.partner) {
        return (
            <AdminLayout title="Partner dashboard">
                <div className="flex flex-col items-center justify-center py-20 text-center">
                    <p className="text-lg text-muted-foreground">No partner record linked to your account.</p>
                    <p className="text-sm text-muted-foreground">Contact the administrator.</p>
                </div>
            </AdminLayout>
        );
    }

    const partner = props.partner;
    const stats = props.stats;

    return (
        <AdminLayout title="Partner dashboard">
            <div className="space-y-6">
                <WelcomeBanner name={partner.name} />

                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <MetricCard label="Total customers" value={stats.total_customers} tone="blue" />
                    <MetricCard label="Active customers" value={stats.active_customers} tone="emerald" />
                    <MetricCard label="Total transactions" value={stats.total_transactions} tone="violet" />
                    <MetricCard label="This month" value={stats.monthly_transactions} tone="amber" />
                </div>

                <div className="flex flex-wrap gap-3">
                    <Link href={route('admin.partner.products')}>
                        <Button variant="outline" size="sm" className="gap-1.5">
                            <Package className="h-4 w-4" /> My Products <ArrowUpRight className="h-3 w-3" />
                        </Button>
                    </Link>
                    <Link href={route('admin.partner.audit-logs')}>
                        <Button variant="outline" size="sm" className="gap-1.5">
                            <BookOpen className="h-4 w-4" /> Audit Logs <ArrowUpRight className="h-3 w-3" />
                        </Button>
                    </Link>
                    <Link href={route('admin.partner.profile')}>
                        <Button variant="outline" size="sm" className="gap-1.5">
                            Profile <ArrowUpRight className="h-3 w-3" />
                        </Button>
                    </Link>
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <Key className="h-4 w-4" />
                                Account
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3 text-sm">
                            <div className="flex justify-between rounded-lg border border-border px-4 py-3">
                                <span className="text-muted-foreground">Code</span>
                                <span className="font-mono text-xs font-medium">{partner.partner_code}</span>
                            </div>
                            <div className="flex justify-between rounded-lg border border-border px-4 py-3">
                                <span className="text-muted-foreground">Email</span>
                                <span className="text-xs">{partner.contact_email}</span>
                            </div>
                            <div className="flex justify-between rounded-lg border border-border px-4 py-3">
                                <span className="text-muted-foreground">Status</span>
                                <span className={`rounded-full px-2.5 py-0.5 text-xs font-medium ${partner.status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'}`}>
                                    {partner.status}
                                </span>
                            </div>
                            <div className="flex justify-between rounded-lg border border-border px-4 py-3">
                                <span className="text-muted-foreground">API key</span>
                                <span className={`flex items-center gap-1 text-xs ${partner.has_api_key ? 'text-emerald-600' : 'text-slate-400'}`}>
                                    <Key className="h-3 w-3" />
                                    {partner.has_api_key ? 'Active' : 'Not generated'}
                                </span>
                            </div>
                            <div className="flex justify-between rounded-lg border border-border px-4 py-3">
                                <span className="flex items-center gap-2 text-muted-foreground">
                                    <Link2 className="h-3.5 w-3.5" />
                                    Connected
                                </span>
                                <span className="text-xs">{partner.connected_at ?? 'Never'}</span>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <Package className="h-4 w-4" />
                                Assigned products
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {props.products.length === 0 ? (
                                <p className="text-sm text-muted-foreground">No products assigned yet.</p>
                            ) : (
                                <div className="space-y-2">
                                    {props.products.map((p) => (
                                        <div key={p.id} className="flex items-center justify-between rounded-lg border border-border px-4 py-2">
                                            <span className="text-sm font-medium">{p.name}</span>
                                            <span className="font-mono text-xs text-muted-foreground">{p.product_code}</span>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Receipt className="h-4 w-4" />
                            Recent transactions
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {props.recentTransactions.length === 0 ? (
                            <p className="text-sm text-muted-foreground">No transactions yet.</p>
                        ) : (
                            <div className="overflow-auto">
                                <table className="w-full min-w-[500px] text-sm">
                                    <thead>
                                        <tr className="border-b border-border text-muted-foreground">
                                            <th className="py-2 text-left font-medium">#</th>
                                            <th className="py-2 text-left font-medium">Customer</th>
                                            <th className="py-2 text-left font-medium">Product</th>
                                            <th className="py-2 text-left font-medium">Amount</th>
                                            <th className="py-2 text-left font-medium">Date</th>
                                            <th className="py-2 text-left font-medium">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {props.recentTransactions.map((t) => (
                                            <tr key={t.id} className="border-b border-border/80 last:border-b-0">
                                                <td className="py-2.5 font-mono text-xs">{t.transaction_number}</td>
                                                <td className="py-2.5">{t.customer_name ?? '-'}</td>
                                                <td className="py-2.5">{t.product_name ?? '-'}</td>
                                                <td className="py-2.5">{new Intl.NumberFormat('en-US', { style: 'currency', currency: t.currency }).format(t.amount)}</td>
                                                <td className="py-2.5 text-xs text-muted-foreground">{t.created_at}</td>
                                                <td className="py-2.5">
                                                    <span className={`rounded-full px-2.5 py-0.5 text-xs font-medium ${
                                                        t.status === 'active' ? 'bg-emerald-100 text-emerald-700' :
                                                        t.status === 'pending' ? 'bg-amber-100 text-amber-700' :
                                                        'bg-red-100 text-red-700'
                                                    }`}>
                                                        {t.status}
                                                    </span>
                                                </td>
                                            </tr>
                                        ))}
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
