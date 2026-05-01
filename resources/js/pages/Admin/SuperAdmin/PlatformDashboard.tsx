import MetricCard from '@/components/admin/MetricCard';
import WelcomeBanner from '@/components/admin/WelcomeBanner';
import AdminLayout from '@/layouts/AdminLayout';
import { usePage } from '@inertiajs/react';
import { Activity, ArrowRight, CheckCircle2, Archive, Database, DollarSign, ShieldCheck, Users } from 'lucide-react';
import { PageProps } from '@/Types';
import { Link } from '@inertiajs/react';
import Chart from 'react-apexcharts';

interface MonthlyPaymentPoint {
    label: string;
    total: number;
}

interface AuditLogRow {
    id: number;
    action: string;
    created_at: string;
}

interface PlatformDashboardProps {
    activeUsers: number;
    activeProducts: number;
    inactiveProducts: number;
    totalCustomers: number;
    coveredCustomers: number;
    notCoveredCustomers: number;
    monthlyPayments: MonthlyPaymentPoint[];
    recentAuditLogs: AuditLogRow[];
    dbHealth: Record<string, string | number | boolean>;
    apiHealth?: {
        requests_24h: number;
        failed_24h: number;
        avg_latency_ms_24h: number;
    };
    stats: {
        transactions: number;
        swap_offers: number;
        occupations: number;
        relationships: number;
        task_types: number;
        referral_usages: number;
        purchases: number;
        purchase_claims: number;
        fund_wallets: number;
    };
}

export default function PlatformDashboard(props: PlatformDashboardProps) {
    const { auth } = usePage<PageProps>().props;
    const monthlyPayments = Array.isArray(props.monthlyPayments) ? props.monthlyPayments : [];
    const recentAuditLogs = Array.isArray(props.recentAuditLogs) ? props.recentAuditLogs : [];
    const dbHealth = props.dbHealth && typeof props.dbHealth === 'object' ? props.dbHealth : {};
    const apiHealth = props.apiHealth ?? { requests_24h: 0, failed_24h: 0, avg_latency_ms_24h: 0 };

    return (
        <AdminLayout title="Platform overview">
            <div className="space-y-6">
                <WelcomeBanner name={auth.user?.name} />
                <section className="space-y-3">
                    <h2 className="text-lg font-semibold text-foreground">System overview</h2>
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <MetricCard label="Active users" value={props.activeUsers} tone="emerald" />

                        <div className="rounded-xl border border-blue-200/70 bg-blue-50/50 p-4 shadow-sm transition-all duration-200 hover:border-blue-300 dark:border-blue-500/25 dark:bg-blue-500/10">
                            <div className="text-sm font-medium text-muted-foreground">Product status count</div>
                            <div className="mt-3 grid grid-cols-1 gap-2">
                                <div className="flex items-center justify-between gap-2 rounded-lg bg-emerald-600 p-3 text-white">
                                    <div className="flex items-center gap-2">
                                        <div className="flex size-8 items-center justify-center rounded bg-white/20">
                                            <CheckCircle2 className="h-4 w-4" />
                                        </div>
                                        <div className="text-xs">Active</div>
                                    </div>
                                    <div className="text-2xl font-semibold">{props.activeProducts}</div>
                                </div>
                                <div className="flex items-center justify-between gap-2 rounded-lg bg-slate-600 p-3 text-white dark:bg-slate-700">
                                    <div className="flex items-center gap-2">
                                        <div className="flex size-8 items-center justify-center rounded bg-white/20">
                                            <Archive className="h-4 w-4" />
                                        </div>
                                        <div className="text-xs">Archived</div>
                                    </div>
                                    <div className="text-2xl font-semibold">{props.inactiveProducts}</div>
                                </div>
                            </div>
                        </div>

                        <div className="rounded-xl border border-violet-200/70 bg-violet-50/50 p-4 shadow-sm transition-all duration-200 hover:border-violet-300 dark:border-violet-500/25 dark:bg-violet-500/10">
                            <div className="text-sm font-medium text-muted-foreground">Customer cover summary</div>
                            <div className="mt-3 space-y-2 text-sm">
                                <div className="flex items-center justify-between gap-2">
                                    <div className="flex items-center gap-2 text-muted-foreground">
                                        <Users className="h-4 w-4 shrink-0" />
                                        <span>Total</span>
                                    </div>
                                    <span className="font-mono font-semibold text-foreground">{props.totalCustomers}</span>
                                </div>
                                <div className="flex items-center justify-between gap-2">
                                    <div className="flex items-center gap-2 text-muted-foreground">
                                        <ShieldCheck className="h-4 w-4 shrink-0 text-emerald-500" />
                                        <span>Total covered</span>
                                    </div>
                                    <span className="font-mono font-semibold text-foreground">{props.coveredCustomers}</span>
                                </div>
                                <div className="flex items-center justify-between gap-2">
                                    <div className="flex items-center gap-2 text-muted-foreground">
                                        <Users className="h-4 w-4 shrink-0" />
                                        <span>Not covered</span>
                                    </div>
                                    <span className="font-mono font-semibold text-foreground">{props.notCoveredCustomers}</span>
                                </div>
                            </div>
                        </div>

                        <div className="rounded-xl border border-amber-200/70 bg-amber-50/50 p-4 shadow-sm transition-all duration-200 hover:border-amber-300 dark:border-amber-500/25 dark:bg-amber-500/10">
                            <div className="flex items-center gap-2 text-sm font-medium text-muted-foreground">
                                <DollarSign className="h-4 w-4 shrink-0" />
                                <span>Payments (this month)</span>
                            </div>
                            <div className="mt-3 h-28">
                                <Chart
                                    type="area"
                                    height={110}
                                    series={[{ name: 'Estimated Revenue', data: monthlyPayments.map((entry) => Number(entry.total ?? 0)) }]}
                                    options={{
                                        chart: { toolbar: { show: false }, sparkline: { enabled: true } },
                                        stroke: { curve: 'smooth', width: 2 },
                                        fill: { type: 'gradient', gradient: { opacityFrom: 0.3, opacityTo: 0.05 } },
                                        xaxis: { categories: monthlyPayments.map((entry) => entry.label) },
                                        colors: ['#0e9f84'],
                                        tooltip: { x: { show: true } },
                                    }}
                                />
                            </div>
                        </div>
                    </div>
                </section>

                <section className="grid gap-4 xl:grid-cols-3">
                    <div className="overflow-auto rounded-xl border border-border bg-card p-4 shadow-sm xl:col-span-2">
                        <div className="mb-3 text-sm font-semibold text-foreground">Recent system activity</div>
                        <table className="w-full min-w-[560px] text-left text-sm">
                            <thead>
                                <tr className="border-b border-border text-muted-foreground">
                                    <th className="px-1 py-2 font-medium">Activity</th>
                                    <th className="px-1 py-2 font-medium">Action</th>
                                    <th className="px-1 py-2 font-medium">Last update</th>
                                </tr>
                            </thead>
                            <tbody>
                                {recentAuditLogs.length === 0 ? (
                                    <tr>
                                        <td colSpan={3} className="px-1 py-6 text-center text-muted-foreground">
                                            No recent activity found.
                                        </td>
                                    </tr>
                                ) : (
                                    recentAuditLogs.map((log) => (
                                        <tr key={log.id} className="border-b border-border/80 text-foreground/90 last:border-b-0">
                                            <td className="px-1 py-3">
                                                <div className="inline-flex items-center gap-2">
                                                    <Activity className="h-4 w-4 text-muted-foreground" />
                                                    <span>System Event</span>
                                                </div>
                                            </td>
                                            <td className="px-1 py-3">{log.action}</td>
                                            <td className="px-1 py-3">{new Date(log.created_at).toLocaleString()}</td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    <div className="space-y-4">
                        <div className="rounded-xl border border-border bg-card p-4 shadow-sm">
                            <div className="mb-3 text-sm font-semibold text-foreground">Database health</div>
                            <div className="space-y-2 text-sm">
                                {Object.entries(dbHealth).map(([key, value]) => (
                                    <div key={key} className="flex items-center justify-between gap-2">
                                        <div className="flex items-center gap-2 text-muted-foreground">
                                            <Database className="h-4 w-4 shrink-0 text-blue-500" />
                                            <span className="capitalize">{key}</span>
                                        </div>
                                        <span className="font-mono text-foreground">{String(value)}</span>
                                    </div>
                                ))}
                            </div>
                        </div>

                        <div className="rounded-xl border border-border bg-card p-4 shadow-sm">
                            <div className="mb-3 text-sm font-semibold text-foreground">API health (24h)</div>
                            <div className="space-y-2 text-sm">
                                <div className="flex items-center justify-between gap-2">
                                    <span className="text-muted-foreground">Requests</span>
                                    <span className="font-mono text-foreground">{apiHealth.requests_24h}</span>
                                </div>
                                <div className="flex items-center justify-between gap-2">
                                    <span className="text-muted-foreground">Failed</span>
                                    <span className="font-mono text-foreground">{apiHealth.failed_24h}</span>
                                </div>
                                <div className="flex items-center justify-between gap-2">
                                    <span className="text-muted-foreground">Avg latency</span>
                                    <span className="font-mono text-foreground">{apiHealth.avg_latency_ms_24h} ms</span>
                                </div>
                            </div>
                        </div>

                        <div className="rounded-xl border border-border bg-card p-4 shadow-sm">
                            <div className="mb-2 text-sm font-semibold text-foreground">Dynamic fields usage</div>
                            <div className="flex flex-wrap gap-2">
                                <span className="rounded-md bg-muted px-2.5 py-1 text-xs text-muted-foreground">customer_data</span>
                                <span className="rounded-md bg-muted px-2.5 py-1 text-xs text-muted-foreground">cover_start</span>
                                <span className="rounded-md bg-muted px-2.5 py-1 text-xs text-muted-foreground">cover_end</span>
                                <span className="rounded-md bg-muted px-2.5 py-1 text-xs text-muted-foreground">custom_rest</span>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </AdminLayout>
    );
}
