import AdminLayout from '@/layouts/AdminLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import {
    BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer, Cell,
} from 'recharts';

interface RevenueRow {
    product_id: number;
    product_name: string;
    partner_id: number;
    partner_name: string;
    bucket: string;
    customer_count: number;
    guide_price: number;
    expected_revenue: number;
}

interface Filters {
    period?: string;
    date_from?: string;
    date_to?: string;
    partner_id?: string;
    product_id?: string;
}

interface OptionItem { id: number; name: string; }

const COLORS = ['#6366f1','#10b981','#f59e0b','#ef4444','#3b82f6','#8b5cf6','#ec4899','#14b8a6'];

const fmt = (v: number) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', maximumFractionDigits: 0 }).format(v);

export default function RevenueByProductReport({
    rows, filters, partners = [], products = [],
}: {
    rows: RevenueRow[];
    filters?: Filters;
    partners?: OptionItem[];
    products?: OptionItem[];
}) {
    const [form, setForm] = useState<Filters>(filters ?? {});

    useEffect(() => {
        const id = window.setInterval(() => {
            router.reload({ only: ['rows'], preserveScroll: true, preserveState: true });
        }, 9000);
        return () => window.clearInterval(id);
    }, []);

    const applyFilters = () => {
        router.get(route('admin.reports.revenue'), form as Record<string, string>, { preserveState: true });
    };

    const totalRevenue = rows.reduce((sum, r) => sum + Number(r.expected_revenue), 0);

    // Aggregate by product for chart
    const chartData = Object.values(
        rows.reduce<Record<string, { product: string; revenue: number; customers: number }>>((acc, r) => {
            const key = r.product_name;
            if (!acc[key]) acc[key] = { product: key, revenue: 0, customers: 0 };
            acc[key].revenue   += Number(r.expected_revenue);
            acc[key].customers += Number(r.customer_count);
            return acc;
        }, {})
    );

    return (
        <AdminLayout title="Revenue by product">
            <div className="space-y-4">
                {/* Filters */}
                <Card>
                    <CardHeader><CardTitle className="text-base">Filters</CardTitle></CardHeader>
                    <CardContent>
                        <div className="grid gap-3 md:grid-cols-6">
                            <select className="rounded-md border border-input bg-background px-3 py-2 text-sm" value={form.period ?? 'monthly'} onChange={(e) => setForm({ ...form, period: e.target.value })}>
                                <option value="daily">Daily</option>
                                <option value="monthly">Monthly</option>
                                <option value="yearly">Yearly</option>
                            </select>
                            <input type="date" className="rounded-md border border-input bg-background px-3 py-2 text-sm" value={form.date_from ?? ''} onChange={(e) => setForm({ ...form, date_from: e.target.value })} />
                            <input type="date" className="rounded-md border border-input bg-background px-3 py-2 text-sm" value={form.date_to ?? ''} onChange={(e) => setForm({ ...form, date_to: e.target.value })} />
                            <select className="rounded-md border border-input bg-background px-3 py-2 text-sm" value={form.partner_id ?? ''} onChange={(e) => setForm({ ...form, partner_id: e.target.value })}>
                                <option value="">All partners</option>
                                {partners.map((p) => <option key={p.id} value={String(p.id)}>{p.name}</option>)}
                            </select>
                            <select className="rounded-md border border-input bg-background px-3 py-2 text-sm" value={form.product_id ?? ''} onChange={(e) => setForm({ ...form, product_id: e.target.value })}>
                                <option value="">All products</option>
                                {products.map((p) => <option key={p.id} value={String(p.id)}>{p.name}</option>)}
                            </select>
                            <button className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90" onClick={applyFilters}>Apply</button>
                        </div>
                    </CardContent>
                </Card>

                {/* Chart */}
                {chartData.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Expected Revenue by Product</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ResponsiveContainer width="100%" height={320}>
                                <BarChart data={chartData} margin={{ top: 10, right: 20, left: 20, bottom: 60 }}>
                                    <CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" />
                                    <XAxis
                                        dataKey="product"
                                        tick={{ fontSize: 12 }}
                                        angle={-30}
                                        textAnchor="end"
                                        interval={0}
                                    />
                                    <YAxis tickFormatter={(v) => fmt(v)} tick={{ fontSize: 11 }} width={90} />
                                    <Tooltip
                                        formatter={(value: number, name: string) => [
                                            name === 'revenue' ? fmt(value) : value,
                                            name === 'revenue' ? 'Expected Revenue' : 'Customers',
                                        ]}
                                    />
                                    <Legend formatter={(v) => v === 'revenue' ? 'Expected Revenue' : 'Customers'} />
                                    <Bar dataKey="revenue" radius={[4, 4, 0, 0]}>
                                        {chartData.map((_, i) => (
                                            <Cell key={i} fill={COLORS[i % COLORS.length]} />
                                        ))}
                                    </Bar>
                                </BarChart>
                            </ResponsiveContainer>
                        </CardContent>
                    </Card>
                )}

                {/* Table */}
                <Card>
                    <CardHeader>
                        <div className="flex items-center justify-between">
                            <CardTitle className="text-base">Expected revenue by partner/product</CardTitle>
                            <span className="text-sm font-semibold text-emerald-600">Total: {fmt(totalRevenue)}</span>
                        </div>
                    </CardHeader>
                    <CardContent>
                        {rows.length === 0 ? (
                            <p className="text-sm text-muted-foreground">No revenue data available.</p>
                        ) : (
                            <div className="overflow-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b border-border text-muted-foreground">
                                            <th className="py-2 text-left font-medium">Product</th>
                                            <th className="py-2 text-left font-medium">Partner</th>
                                            <th className="py-2 text-left font-medium">Period</th>
                                            <th className="py-2 text-right font-medium">Customers</th>
                                            <th className="py-2 text-right font-medium">Guide Price</th>
                                            <th className="py-2 text-right font-medium">Expected Revenue</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {rows.map((row, i) => (
                                            <tr key={`${row.product_id}-${row.partner_id}-${row.bucket}-${i}`} className="border-b border-border/60 hover:bg-muted/30">
                                                <td className="py-2 font-medium">{row.product_name}</td>
                                                <td className="py-2">{row.partner_name}</td>
                                                <td className="py-2">{row.bucket}</td>
                                                <td className="py-2 text-right">{row.customer_count}</td>
                                                <td className="py-2 text-right">{fmt(Number(row.guide_price))}</td>
                                                <td className="py-2 text-right font-semibold text-emerald-600">{fmt(Number(row.expected_revenue))}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                    <tfoot>
                                        <tr className="border-t-2 border-border">
                                            <td className="py-2 font-bold">Total</td>
                                            <td /><td />
                                            <td className="py-2 text-right font-bold">{rows.reduce((s, r) => s + Number(r.customer_count), 0)}</td>
                                            <td />
                                            <td className="py-2 text-right font-bold text-emerald-600">{fmt(totalRevenue)}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AdminLayout>
    );
}
