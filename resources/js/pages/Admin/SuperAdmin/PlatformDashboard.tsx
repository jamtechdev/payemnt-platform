import MetricCard from '@/components/admin/MetricCard';
import WelcomeBanner from '@/components/admin/WelcomeBanner';
import AdminLayout from '@/layouts/AdminLayout';
import { usePage } from '@inertiajs/react';
import { Area, AreaChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';
import { Activity, CheckCircle2, Archive, Database, DollarSign, ShieldCheck, Users } from 'lucide-react';
import { Icon } from '@/components/icon';
import { PageProps } from '@/Types';

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
}

export default function PlatformDashboard(props: PlatformDashboardProps) {
    const { auth } = usePage<PageProps>().props;

    return (
        <AdminLayout title="Platform overview">
            <div className="space-y-5">
                <WelcomeBanner name={auth.user?.name} />
                <h2 className="text-lg font-semibold text-slate-800 dark:text-slate-200">System overview</h2>
                <div className="grid gap-4 lg:grid-cols-4">
<MetricCard label="Active users" value={props.activeUsers} icon={Users} />
<div className="rounded-xl border-1 border-primary/90 bg-card p-4 shadow-sm transition-all duration-300 hover:border-primary hover:shadow-lg hover:-translate-y-1">
                        <div className="text-sm text-slate-500">Product Status Count</div>
                        <div className="mt-2 grid grid-cols-1 gap-2">
                            <div className="flex items-center justify-between gap-2 rounded bg-emerald-600 p-3 text-white">
                                <div className="flex items-center gap-2">
                                    <div className="flex size-8 items-center justify-center rounded bg-white/20">
                                        <CheckCircle2 className="h-4 w-4" />
                                    </div>
                                    <div>
                                        <div className="text-xs">Active</div>
                                    </div>
                                </div>
                                <div className="text-2xl font-semibold">{props.activeProducts}</div>
                            </div>
                            <div className="flex items-center justify-between gap-2 rounded bg-slate-600 p-3 text-white">
                                <div className="flex items-center gap-2">
                                    <div className="flex size-8 items-center justify-center rounded bg-white/20">
                                        <Archive className="h-4 w-4" />
                                    </div>
                                    <div>
                                        <div className="text-xs">Archived</div>
                                    </div>
                                </div>
                                <div className="text-2xl font-semibold">{props.inactiveProducts}</div>
                            </div>
                        </div>
                    </div>
                    <div className="rounded-xl border-1 border-primary/90 bg-card p-4 shadow-sm transition-all duration-300 hover:border-primary hover:shadow-lg hover:-translate-y-1">
                        <div className="text-sm text-slate-500">Customer Cover Summary</div>
                        <div className="mt-2 space-y-1 text-sm">
                            <div className="flex items-center justify-between gap-2">
                                <div className="flex items-center gap-2">
                                    <Users className="h-4 w-4 text-slate-500 shrink-0" />
                                    <span>Total</span>
                                </div>
                                <span className="font-mono font-semibold">{props.totalCustomers}</span>
                            </div>
                            <div className="flex items-center justify-between gap-2">
                                <div className="flex items-center gap-2">
                                    <ShieldCheck className="h-4 w-4 text-emerald-500 shrink-0" />
                                    <span>Total covered</span>
                                </div>
                                <span className="font-mono font-semibold">{props.coveredCustomers}</span>
                            </div>
                            <div className="flex items-center justify-between gap-2">
                                <div className="flex items-center gap-2">
                                    <Users className="h-4 w-4 text-slate-500 shrink-0" />
                                    <span>Not covered</span>
                                </div>
                                <span className="font-mono font-semibold">{props.notCoveredCustomers}</span>
                            </div>
                        </div>
                    </div>
                    <div className="rounded-xl border-1 border-primary/90 bg-card p-4 shadow-sm transition-all duration-300 hover:border-primary hover:shadow-lg hover:-translate-y-1">
                        <div className="flex items-center gap-2 text-sm text-slate-500">
                            <DollarSign className="h-4 w-4 shrink-0" />
                            <span>Payments (This Month)</span>
                        </div>
                        <div className="mt-2 h-28">
                            <ResponsiveContainer width="100%" height="100%">
                                <AreaChart data={props.monthlyPayments}>
                                    <XAxis dataKey="label" hide />
                                    <YAxis hide />
                                    <Tooltip />
                                    <Area type="monotone" dataKey="total" stroke="#2563eb" fill="#bfdbfe" />
                                </AreaChart>
                            </ResponsiveContainer>
                        </div>
                    </div>
                </div>

                <div className="grid gap-4 lg:grid-cols-3">
                    <div className="rounded-xl border-1 border-primary/90 bg-card p-4 shadow-sm transition-all duration-300 hover:border-primary hover:shadow-lg hover:-translate-y-1 lg:col-span-2 overflow-auto">
                        <div className="mb-2 text-sm font-semibold text-slate-700">Recent System Activity</div>
                        <table className="w-full text-left text-sm">
                            <thead>
                                <tr className="border-b text-slate-500">
                                    <th className="py-2">Activity</th>
                                    <th className="py-2">Action</th>
                                    <th className="py-2">Last Update</th>
                                </tr>
                            </thead>
                            <tbody>
                                {props.recentAuditLogs.map((log) => (
                                    <tr key={log.id} className="border-b">
                                        <td className="py-2">
                                            <Activity className="h-4 w-4 text-slate-500 mr-2 inline" />
                                            {log.action}
                                        </td>
                                        <td className="py-2">{new Date(log.created_at).toLocaleString()}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    <div className="space-y-4">
                        <div className="rounded-xl border-1 border-primary/90 bg-card p-4 shadow-sm transition-all duration-300 hover:border-primary hover:shadow-lg hover:-translate-y-1">
                            <div className="mb-2 text-sm font-semibold text-slate-700">Database Health</div>
                            <div className="space-y-1 text-sm">
                                {Object.entries(props.dbHealth).map(([key, value]) => (
                                    <div key={key} className="flex items-center justify-between gap-2">
                                        <div className="flex items-center gap-2">
                                            <Database className="h-4 w-4 text-blue-500 shrink-0" />
                                            <span className="capitalize">{key}</span>
                                        </div>
                                        <span className="font-mono">{String(value)}</span>
                                    </div>
                                ))}
                            </div>
                        </div>
                        <div className="rounded-xl border-1 border-primary/90 bg-card p-4 shadow-sm transition-all duration-300 hover:border-primary hover:shadow-lg hover:-translate-y-1">
                            <div className="mb-2 text-sm font-semibold text-slate-700">Dynamic Fields Usage</div>
                            <div className="flex flex-wrap gap-1">
                                <span className="rounded bg-slate-100 px-2 py-1 text-xs dark:bg-gray-800">customer_data</span>
                                <span className="rounded bg-slate-100 px-2 py-1 text-xs dark:bg-gray-800">cover_start</span>
                                <span className="rounded bg-slate-100 px-2 py-1 text-xs dark:bg-gray-800">cover_end</span>
                                <span className="rounded bg-slate-100 px-2 py-1 text-xs dark:bg-gray-800">custom_rest</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}
