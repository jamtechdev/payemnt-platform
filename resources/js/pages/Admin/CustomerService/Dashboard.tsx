import AdminLayout from '@/layouts/AdminLayout';
import MetricCard from '@/components/admin/MetricCard';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { router } from '@inertiajs/react';
import { AlertTriangle, Download, Users } from 'lucide-react';
import { Button } from '@/components/ui/button';

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

interface CustomerServiceDashboardProps {
    totalCustomers: number;
    activeCustomers: number;
    newThisWeek: number;
    customersByPartner: PartnerCount[];
    expiringSoon: ExpiringSoonRow[];
    expiringSoonCount: number;
}

export default function Dashboard(props: CustomerServiceDashboardProps) {
    const inactiveCustomers = Math.max(props.totalCustomers - props.activeCustomers, 0);

    return (
        <AdminLayout title="Customer service dashboard">
            <div className="space-y-6">
                {/* BRD CS-001: Summary metrics */}
                <div className="grid gap-4 md:grid-cols-4">
                    <MetricCard label="Total customers" value={props.totalCustomers} />
                    <MetricCard label="Active customers" value={props.activeCustomers} valueClassName="text-emerald-600" />
                    <MetricCard label="Needs follow-up" value={inactiveCustomers} valueClassName="text-amber-600" />
                    <MetricCard label="New this week" value={props.newThisWeek} valueClassName="text-blue-600" />
                </div>

                {/* BRD open question 4: Expiring covers alert */}
                {props.expiringSoonCount > 0 && (
                    <Card className="border-amber-200 bg-amber-50 dark:border-amber-500/30 dark:bg-amber-500/10">
                        <CardHeader className="pb-2">
                            <div className="flex items-center justify-between">
                                <CardTitle className="flex items-center gap-2 text-base text-amber-800 dark:text-amber-400">
                                    <AlertTriangle className="h-4 w-4" />
                                    Covers expiring within 30 days ({props.expiringSoonCount})
                                </CardTitle>
                                <Button
                                    size="sm"
                                    variant="outline"
                                    className="border-amber-300 text-amber-700 hover:bg-amber-100"
                                    onClick={() => window.open(route('admin.customers.export-expiring'), '_blank')}
                                >
                                    <Download className="mr-1 h-3.5 w-3.5" />
                                    Download CSV
                                </Button>
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

                {/* BRD CS-001: Customer count per partner */}
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
            </div>
        </AdminLayout>
    );
}
