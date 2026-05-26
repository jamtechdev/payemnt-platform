import AdminLayout from '@/layouts/AdminLayout';
import MetricCard from '@/components/admin/MetricCard';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { usePage, Link, router } from '@inertiajs/react';
import { PageProps } from '@/Types';
import { AlertTriangle, Download, Users, UserPlus, ArrowUpRight, Search, Activity, Clock } from 'lucide-react';

interface PartnerCount {
    partner_id: number;
    partner_name: string;
    total: number;
}

interface ExpiringSoonRow {
    uuid: string;
    full_name: string;
    email: string;
    cover_end_date: string;
    partner_name: string;
    product_name: string;
}

interface RecentActivityRow {
    id: number;
    action: string;
    actor_name: string;
    entity_type: string;
    created_at: string;
}

interface CustomerServiceDashboardProps {
    totalCustomers: number;
    activeCustomers: number;
    newThisWeek: number;
    customersByPartner: PartnerCount[];
    expiringSoon: ExpiringSoonRow[];
    expiringSoonCount: number;
    recentActivity: RecentActivityRow[];
}

export default function Dashboard(props: CustomerServiceDashboardProps) {
    const inactiveCustomers = Math.max(props.totalCustomers - props.activeCustomers, 0);
    const { auth } = usePage<PageProps>().props;
    const perms = auth.permissions ?? [];

    const canViewCustomers = perms.includes('customers.view_list') || perms.includes('customers.view_detail') || perms.includes('dashboard.customer_overview');
    const canExport = perms.includes('customers.export');

    return (
        <AdminLayout title="Customer service dashboard">
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-800">Customer Service</h1>
                        <p className="text-sm text-muted-foreground">Customer overview and alerts</p>
                    </div>
                    {canViewCustomers && (
                        <div className="flex flex-wrap gap-2">
                            <Link href={route('admin.customers.index')}>
                                <Button variant="outline" size="sm" className="gap-1.5">
                                    <Users className="h-4 w-4" /> All customers <ArrowUpRight className="h-3 w-3" />
                                </Button>
                            </Link>
                        </div>
                    )}
                </div>

                {(canViewCustomers || perms.includes('dashboard.metrics_overview')) && (
                    <div className="grid gap-4 md:grid-cols-4">
                        <MetricCard label="Total customers" value={props.totalCustomers} icon={<Users className="h-4 w-4" />} />
                        <MetricCard label="Active customers" value={props.activeCustomers} valueClassName="text-emerald-600" icon={<UserPlus className="h-4 w-4" />} />
                        <MetricCard label="Needs follow-up" value={inactiveCustomers} valueClassName="text-amber-600" />
                        <MetricCard label="New this week" value={props.newThisWeek} valueClassName="text-blue-600" />
                    </div>
                )}

                <div className="grid gap-4 lg:grid-cols-3">
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <Activity className="h-4 w-4" />
                                Recent Activity
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                {props.recentActivity.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">No recent activity found.</p>
                                ) : (
                                    props.recentActivity.map((activity) => (
                                        <div key={activity.id} className="flex items-start gap-3 rounded-lg border border-border/50 p-3 transition-colors hover:bg-muted/50">
                                            <div className="mt-0.5 rounded-full bg-primary/10 p-1.5">
                                                <Clock className="h-3.5 w-3.5 text-primary" />
                                            </div>
                                            <div className="flex-1 space-y-1">
                                                <div className="flex items-center justify-between">
                                                    <p className="text-sm font-medium leading-none text-foreground capitalize">
                                                        {activity.action.replace(/_/g, ' ')}
                                                    </p>
                                                    <span className="text-[10px] text-muted-foreground">
                                                        {new Date(activity.created_at).toLocaleString()}
                                                    </span>
                                                </div>
                                                <p className="text-xs text-muted-foreground">
                                                    <span className="font-medium text-foreground">{activity.actor_name}</span> performed action on {activity.entity_type}
                                                </p>
                                            </div>
                                        </div>
                                    ))
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Quick actions</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2">
                            <Link href={route('admin.customers.index')}>
                                <div className="flex items-center justify-between rounded-lg border border-border px-4 py-3 transition hover:bg-accent/50">
                                    <div className="flex items-center gap-2">
                                        <Search className="h-4 w-4 text-muted-foreground" />
                                        <span className="text-sm font-medium">Browse customers</span>
                                    </div>
                                    <ArrowUpRight className="h-4 w-4 text-muted-foreground" />
                                </div>
                            </Link>
                            {canExport && (
                                <Link href={route('admin.customers.export-expiring')}>
                                    <div className="flex items-center justify-between rounded-lg border border-border px-4 py-3 transition hover:bg-accent/50">
                                        <div className="flex items-center gap-2">
                                            <Download className="h-4 w-4 text-muted-foreground" />
                                            <span className="text-sm font-medium">Export expiring covers</span>
                                        </div>
                                        <ArrowUpRight className="h-4 w-4 text-muted-foreground" />
                                    </div>
                                </Link>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {canViewCustomers && props.expiringSoonCount > 0 && (
                    <Card className="border-amber-200 bg-amber-50">
                        <CardHeader className="pb-2">
                            <div className="flex items-center justify-between">
                                <CardTitle className="flex items-center gap-2 text-base text-amber-800">
                                    <AlertTriangle className="h-4 w-4" />
                                    Covers expiring within 30 days ({props.expiringSoonCount})
                                </CardTitle>
                                {canExport && (
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        className="border-amber-300 text-amber-700 hover:bg-amber-100"
                                        onClick={() => window.open(route('admin.customers.export-expiring'), '_blank')}
                                    >
                                        <Download className="mr-1 h-3.5 w-3.5" />
                                        Download CSV
                                    </Button>
                                )}
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div className="overflow-auto">
                                <table className="w-full min-w-[600px] text-sm">
                                    <thead>
                                        <tr className="border-b border-amber-200 text-amber-700">
                                            <th className="py-2 text-left font-medium">Customer</th>
                                            <th className="py-2 text-left font-medium">Email</th>
                                            <th className="py-2 text-left font-medium">Partner</th>
                                            <th className="py-2 text-left font-medium">Product</th>
                                            <th className="py-2 text-left font-medium">Cover End</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {props.expiringSoon.map((row) => (
                                            <tr
                                                key={row.uuid}
                                                className="cursor-pointer border-b border-amber-100 hover:bg-amber-100/50"
                                                onClick={() => router.visit(route('admin.customers.show', row.uuid))}
                                            >
                                                <td className="py-2 font-medium text-slate-800">{row.full_name}</td>
                                                <td className="py-2 text-slate-600">{row.email}</td>
                                                <td className="py-2 text-slate-600">{row.partner_name}</td>
                                                <td className="py-2 text-slate-600">{row.product_name}</td>
                                                <td className="py-2 font-medium text-amber-700">{row.cover_end_date}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                )}

                <div className="grid gap-4 md:grid-cols-2">
                    {canViewCustomers && (
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <Users className="h-4 w-4" />
                                    Customers by partner
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {props.customersByPartner.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">No partner data available.</p>
                                ) : (
                                    <div className="space-y-2">
                                        {props.customersByPartner.map((row) => (
                                            <div key={row.partner_id} className="flex items-center justify-between rounded-lg border border-border px-4 py-2">
                                                <span className="text-sm font-medium text-foreground">{row.partner_name}</span>
                                                <span className="rounded-full bg-primary/10 px-3 py-0.5 text-sm font-semibold text-primary">
                                                    {row.total}
                                                </span>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    )}
                </div>
            </div>
        </AdminLayout>
    );
}
