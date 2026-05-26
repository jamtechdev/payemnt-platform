import AdminLayout from '@/layouts/AdminLayout';
import MetricCard from '@/components/admin/MetricCard';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { usePage, Link } from '@inertiajs/react';
import { PageProps } from '@/Types';
import { BarChart2, DollarSign, ArrowUpRight, Users, TrendingUp, FileText } from 'lucide-react';

interface ProductCount {
    product_id: number;
    product_name: string;
    total: number;
}

interface ProductRevenue {
    product_id: number;
    product_name?: string;
    total_revenue: number;
    breakdown?: { currency: string; amount: number }[];
}

interface RevenueBreakdown {
    currency: string;
    total: number;
}

interface ReconciliationDashboardProps {
    monthlyCustomers: number;
    monthlyRevenue: number;
    preferredCurrency: string;
    customersByProduct: ProductCount[];
    revenueByProduct: ProductRevenue[];
    revenueBreakdown?: RevenueBreakdown[];
}

export default function ReconciliationDashboard(props: ReconciliationDashboardProps) {
    const currency = props.preferredCurrency || 'USD';
    const { auth } = usePage<PageProps>().props;
    const perms = auth.permissions ?? [];

    const canViewCustomers = perms.includes('customers.view_list') || perms.includes('customers.view_detail');
    const canViewReports = perms.includes('reports.view') || perms.includes('reports.revenue_by_product') || perms.includes('reports.customer_acquisition');
    const canViewPartnerPerformance = perms.includes('reports.partner_performance');

    return (
        <AdminLayout title="Reconciliation dashboard">
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-800">Reconciliation</h1>
                        <p className="text-sm text-muted-foreground">Revenue and customer overview</p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        {canViewReports && (
                            <>
                                <Link href={route('admin.reports.customer-acquisition')}>
                                    <Button variant="outline" size="sm" className="gap-1.5">
                                        <TrendingUp className="h-4 w-4" /> Acquisition <ArrowUpRight className="h-3 w-3" />
                                    </Button>
                                </Link>
                                <Link href={route('admin.reports.revenue')}>
                                    <Button variant="outline" size="sm" className="gap-1.5">
                                        <DollarSign className="h-4 w-4" /> Revenue <ArrowUpRight className="h-3 w-3" />
                                    </Button>
                                </Link>
                            </>
                        )}
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-3">
                    {(perms.includes('dashboard.customer_overview') || canViewCustomers) && (
                        <MetricCard label="Monthly customers" value={props.monthlyCustomers} icon={<Users className="h-4 w-4" />} />
                    )}
                    {(perms.includes('dashboard.metrics_overview') || perms.includes('reports.revenue_by_product') || perms.includes('reports.view')) && (
                        <div className="rounded-xl border border-emerald-200/70 bg-emerald-50/50 p-4 shadow-sm transition-all duration-200 hover:border-emerald-300">
                            <div className="text-sm font-medium text-muted-foreground">Monthly revenue (converted to {currency})</div>
                            <div className="mt-1 text-2xl font-semibold text-emerald-600">
                                {new Intl.NumberFormat('en-US', { style: 'currency', currency: currency }).format(props.monthlyRevenue)}
                            </div>
                            {props.revenueBreakdown && props.revenueBreakdown.length > 1 && (
                                <div className="mt-2 border-t border-dashed border-emerald-200 pt-2">
                                    <div className="mb-1 text-[10px] font-medium text-muted-foreground">By currency (original)</div>
                                    <div className="flex flex-wrap gap-1.5">
                                        {props.revenueBreakdown.map((b, i) => (
                                            <span key={i} className="rounded bg-white/60 px-1.5 py-0.5 text-[10px] font-mono text-emerald-800">
                                                {new Intl.NumberFormat('en-US', { style: 'currency', currency: b.currency, maximumFractionDigits: 0 }).format(b.total)}
                                            </span>
                                        ))}
                                    </div>
                                </div>
                            )}
                        </div>
                    )}
                    <div className="rounded-xl border border-blue-200/70 bg-blue-50/50 p-4 shadow-sm">
                        <div className="text-sm font-medium text-muted-foreground">Active products</div>
                        <div className="mt-1 text-2xl font-semibold text-blue-600">{props.revenueByProduct.length}</div>
                        <div className="mt-2 border-t border-dashed border-blue-200 pt-2">
                            <div className="flex flex-wrap gap-1.5">
                                {props.revenueByProduct.map((r) => (
                                    <span key={r.product_id} className="rounded bg-white/60 px-1.5 py-0.5 text-[10px] font-mono text-blue-800">
                                        {r.product_name || `#${r.product_id}`}
                                    </span>
                                ))}
                            </div>
                        </div>
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    {canViewCustomers && (
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <BarChart2 className="h-4 w-4" />
                                    Customers by product
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {props.customersByProduct.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">No data available.</p>
                                ) : (
                                    <div className="space-y-2">
                                        {props.customersByProduct.map((row) => (
                                            <div key={row.product_id} className="flex items-center justify-between rounded-lg border border-border px-4 py-2">
                                                <span className="text-sm font-medium text-foreground">{row.product_name}</span>
                                                <span className="rounded-full bg-blue-100 px-3 py-0.5 text-sm font-semibold text-blue-700">
                                                    {row.total}
                                                </span>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    )}

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <DollarSign className="h-4 w-4" />
                                Revenue by product (this month)
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {props.revenueByProduct.length === 0 ? (
                                <p className="text-sm text-muted-foreground">No revenue data available.</p>
                            ) : (
                                <div className="space-y-2">
                                    {props.revenueByProduct.map((row) => (
                                        <div key={row.product_id} className="flex flex-col gap-1 rounded-lg border border-border px-4 py-2">
                                            <div className="flex items-center justify-between">
                                                <span className="text-sm font-medium text-foreground">{row.product_name || `Product #${row.product_id}`}</span>
                                                <span className="rounded-full bg-emerald-100 px-3 py-0.5 text-sm font-semibold text-emerald-700">
                                                    {new Intl.NumberFormat('en-US', { style: 'currency', currency: currency }).format(Number(row.total_revenue))}
                                                </span>
                                            </div>
                                            {row.breakdown && row.breakdown.length > 1 && (
                                                <div className="flex flex-wrap gap-2 pt-1 border-t border-dashed border-border mt-1">
                                                    {row.breakdown.map((b, idx) => (
                                                        <span key={idx} className="text-[10px] text-muted-foreground">
                                                            {new Intl.NumberFormat('en-US', { style: 'currency', currency: b.currency }).format(b.amount)}
                                                        </span>
                                                    ))}
                                                </div>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {canViewReports && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <FileText className="h-4 w-4" />
                                Reports
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid gap-3 sm:grid-cols-3">
                                {perms.includes('reports.customer_acquisition') && (
                                    <Link href={route('admin.reports.customer-acquisition')}>
                                        <div className="flex items-center justify-between rounded-lg border border-border px-4 py-3 transition hover:bg-accent/50">
                                            <span className="text-sm font-medium">Customer acquisition</span>
                                            <ArrowUpRight className="h-4 w-4 text-muted-foreground" />
                                        </div>
                                    </Link>
                                )}
                                {perms.includes('reports.revenue_by_product') && (
                                    <Link href={route('admin.reports.revenue')}>
                                        <div className="flex items-center justify-between rounded-lg border border-border px-4 py-3 transition hover:bg-accent/50">
                                            <span className="text-sm font-medium">Revenue by product</span>
                                            <ArrowUpRight className="h-4 w-4 text-muted-foreground" />
                                        </div>
                                    </Link>
                                )}
                                {canViewPartnerPerformance && (
                                    <Link href={route('admin.partners.performance')}>
                                        <div className="flex items-center justify-between rounded-lg border border-border px-4 py-3 transition hover:bg-accent/50">
                                            <span className="text-sm font-medium">Partner performance</span>
                                            <ArrowUpRight className="h-4 w-4 text-muted-foreground" />
                                        </div>
                                    </Link>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AdminLayout>
    );
}
