import AdminLayout from '@/layouts/AdminLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { router } from '@inertiajs/react';
import {
    Area,
    AreaChart,
    CartesianGrid,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';
import { useEffect } from 'react';
import { TrendingUp, TrendingDown, Users, DollarSign } from 'lucide-react';

interface Point {
    bucket: string;
    customer_count: number;
    total_revenue: number;
}

interface PartnerSeries {
    partner_id: number;
    partner_name: string;
    growth_delta: number;
    trend: 'growth' | 'loss';
    points: Point[];
}

function StatBadge({ value, label, icon }: { value: string | number; label: string; icon: React.ReactNode }) {
    return (
        <div className="flex items-center gap-2 rounded-lg bg-slate-50 px-3 py-2">
            <span className="text-slate-400">{icon}</span>
            <div>
                <p className="text-xs text-slate-500">{label}</p>
                <p className="text-sm font-semibold text-slate-800">{value}</p>
            </div>
        </div>
    );
}

export default function PartnerPerformance({ series = [], months = 12 }: { series?: PartnerSeries[]; months?: number }) {
    useEffect(() => {
        const id = window.setInterval(() => {
            router.reload({ only: ['series'], preserveScroll: true, preserveState: true });
        }, 10000);
        return () => window.clearInterval(id);
    }, []);

    const totalCustomers = series.reduce((sum, p) => {
        const last = p.points[p.points.length - 1];
        return sum + (last?.customer_count ?? 0);
    }, 0);

    const totalRevenue = series.reduce((sum, p) => {
        return sum + p.points.reduce((s, pt) => s + pt.total_revenue, 0);
    }, 0);

    const growingCount = series.filter((p) => p.trend === 'growth').length;

    return (
        <AdminLayout title="Partner Performance">
            <div className="space-y-6">

                {/* Page Header */}
                <div>
                    <h1 className="text-xl font-semibold text-slate-800">Partner Performance</h1>
                    <p className="mt-1 text-sm text-slate-500">
                        Customer growth and revenue trends across all partners — last {months} months
                    </p>
                </div>

                {/* Summary Strip */}
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <Card className="border-slate-200">
                        <CardContent className="flex items-center gap-4 pt-5">
                            <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                                <Users className="h-5 w-5" />
                            </span>
                            <div>
                                <p className="text-xs text-slate-500">Total Active Partners</p>
                                <p className="text-2xl font-bold text-slate-800">{series.length}</p>
                            </div>
                        </CardContent>
                    </Card>
                    <Card className="border-slate-200">
                        <CardContent className="flex items-center gap-4 pt-5">
                            <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                                <TrendingUp className="h-5 w-5" />
                            </span>
                            <div>
                                <p className="text-xs text-slate-500">Partners Growing</p>
                                <p className="text-2xl font-bold text-slate-800">{growingCount}</p>
                            </div>
                        </CardContent>
                    </Card>
                    <Card className="border-slate-200">
                        <CardContent className="flex items-center gap-4 pt-5">
                            <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-50 text-violet-600">
                                <DollarSign className="h-5 w-5" />
                            </span>
                            <div>
                                <p className="text-xs text-slate-500">Total Revenue (Period)</p>
                                <p className="text-2xl font-bold text-slate-800">
                                    {totalRevenue.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 0 })}
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Partner Cards */}
                {series.length === 0 ? (
                    <Card>
                        <CardContent className="py-16 text-center">
                            <p className="text-sm text-slate-400">No performance data available for this period.</p>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid grid-cols-1 gap-5 lg:grid-cols-2">
                        {series.map((partner) => {
                            const totalTxn = partner.points.reduce((s, p) => s + p.customer_count, 0);
                            const totalRev = partner.points.reduce((s, p) => s + p.total_revenue, 0);
                            const isGrowth = partner.trend === 'growth';

                            return (
                                <Card key={partner.partner_id} className="border-slate-200 shadow-sm">
                                    <CardHeader className="pb-3">
                                        <div className="flex items-start justify-between">
                                            <div>
                                                <CardTitle className="text-base font-semibold text-slate-800">
                                                    {partner.partner_name}
                                                </CardTitle>
                                                <p className="mt-0.5 text-xs text-slate-400">
                                                    {partner.points.length > 0
                                                        ? `${partner.points[0].bucket} — ${partner.points[partner.points.length - 1].bucket}`
                                                        : `Last ${months} months`}
                                                </p>
                                            </div>
                                            <span
                                                className={`inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold ${
                                                    isGrowth
                                                        ? 'bg-emerald-50 text-emerald-700'
                                                        : 'bg-red-50 text-red-600'
                                                }`}
                                            >
                                                {isGrowth ? (
                                                    <TrendingUp className="h-3 w-3" />
                                                ) : (
                                                    <TrendingDown className="h-3 w-3" />
                                                )}
                                                {isGrowth ? '+' : ''}{partner.growth_delta} transactions
                                            </span>
                                        </div>

                                        {/* Stats Row */}
                                        <div className="mt-3 flex flex-wrap gap-2">
                                            <StatBadge
                                                icon={<Users className="h-3.5 w-3.5" />}
                                                label="Total Transactions"
                                                value={totalTxn.toLocaleString()}
                                            />
                                            <StatBadge
                                                icon={<DollarSign className="h-3.5 w-3.5" />}
                                                label="Total Revenue"
                                                value={totalRev.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 0 })}
                                            />
                                        </div>
                                    </CardHeader>

                                    <CardContent className="pb-4">
                                        <div className="h-44">
                                            <ResponsiveContainer width="100%" height="100%">
                                                <AreaChart data={partner.points} margin={{ top: 4, right: 4, left: -20, bottom: 0 }}>
                                                    <defs>
                                                        <linearGradient id={`grad-${partner.partner_id}`} x1="0" y1="0" x2="0" y2="1">
                                                            <stop
                                                                offset="5%"
                                                                stopColor={isGrowth ? '#10b981' : '#ef4444'}
                                                                stopOpacity={0.15}
                                                            />
                                                            <stop
                                                                offset="95%"
                                                                stopColor={isGrowth ? '#10b981' : '#ef4444'}
                                                                stopOpacity={0}
                                                            />
                                                        </linearGradient>
                                                    </defs>
                                                    <CartesianGrid strokeDasharray="3 3" stroke="#f1f5f9" vertical={false} />
                                                    <XAxis
                                                        dataKey="bucket"
                                                        tick={{ fontSize: 10, fill: '#94a3b8' }}
                                                        axisLine={false}
                                                        tickLine={false}
                                                    />
                                                    <YAxis
                                                        tick={{ fontSize: 10, fill: '#94a3b8' }}
                                                        axisLine={false}
                                                        tickLine={false}
                                                        allowDecimals={false}
                                                    />
                                                    <Tooltip
                                                        contentStyle={{
                                                            fontSize: 12,
                                                            borderRadius: 8,
                                                            border: '1px solid #e2e8f0',
                                                            boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.05)',
                                                        }}
                                                        formatter={(value: number, name: string) => [
                                                            value.toLocaleString(),
                                                            name === 'customer_count' ? 'Transactions' : 'Revenue',
                                                        ]}
                                                    />
                                                    <Area
                                                        type="monotone"
                                                        dataKey="customer_count"
                                                        stroke={isGrowth ? '#10b981' : '#ef4444'}
                                                        strokeWidth={2}
                                                        fill={`url(#grad-${partner.partner_id})`}
                                                        dot={{ r: 4, fill: isGrowth ? '#10b981' : '#ef4444', strokeWidth: 0 }}
                                                        activeDot={{ r: 5, strokeWidth: 0 }}
                                                        name="customer_count"
                                                    />
                                                </AreaChart>
                                            </ResponsiveContainer>
                                        </div>
                                    </CardContent>
                                </Card>
                            );
                        })}
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}
