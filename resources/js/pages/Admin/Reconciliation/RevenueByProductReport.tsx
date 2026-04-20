import AdminLayout from '@/layouts/AdminLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { router } from '@inertiajs/react';
import { useState } from 'react';

interface RevenueRow {
    product_id: number;
    total_revenue: number;
    payment_count: number;
}

interface Filters {
    period?: string;
    date_from?: string;
    date_to?: string;
}

export default function RevenueByProductReport({ rows, filters }: { rows: RevenueRow[]; filters?: Filters }) {
    const [form, setForm] = useState<Filters>(filters ?? {});

    const applyFilters = () => {
        router.get(route('admin.reports.revenue'), form as Record<string, string>, { preserveState: true });
    };

    const totalRevenue = rows.reduce((sum, r) => sum + Number(r.total_revenue), 0);

    return (
        <AdminLayout title="Revenue by product">
            <div className="space-y-4">
                {/* BRD REC-004: Time period filtering */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Filters</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-3 md:grid-cols-4">
                            <select
                                className="rounded-md border border-input bg-background px-3 py-2 text-sm"
                                value={form.period ?? 'monthly'}
                                onChange={(e) => setForm({ ...form, period: e.target.value })}
                            >
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                            </select>
                            <input
                                type="date"
                                className="rounded-md border border-input bg-background px-3 py-2 text-sm"
                                value={form.date_from ?? ''}
                                onChange={(e) => setForm({ ...form, date_from: e.target.value })}
                            />
                            <input
                                type="date"
                                className="rounded-md border border-input bg-background px-3 py-2 text-sm"
                                value={form.date_to ?? ''}
                                onChange={(e) => setForm({ ...form, date_to: e.target.value })}
                            />
                            <button
                                className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                                onClick={applyFilters}
                            >
                                Apply
                            </button>
                        </div>
                    </CardContent>
                </Card>

                {/* BRD REC-003: Revenue summary */}
                <Card>
                    <CardHeader>
                        <div className="flex items-center justify-between">
                            <CardTitle className="text-base">Revenue summary by product</CardTitle>
                            <span className="text-sm font-semibold text-emerald-600">
                                Total: {new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(totalRevenue)}
                            </span>
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
                                            <th className="py-2 text-right font-medium">Payments</th>
                                            <th className="py-2 text-right font-medium">Revenue</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {rows.map((row) => (
                                            <tr key={row.product_id} className="border-b border-border/60 hover:bg-muted/30">
                                                <td className="py-2 font-medium text-foreground">Product #{row.product_id}</td>
                                                <td className="py-2 text-right text-foreground">{row.payment_count}</td>
                                                <td className="py-2 text-right font-semibold text-emerald-600">
                                                    {new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(Number(row.total_revenue))}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                    <tfoot>
                                        <tr className="border-t-2 border-border">
                                            <td className="py-2 font-bold text-foreground">Total</td>
                                            <td className="py-2 text-right font-bold text-foreground">
                                                {rows.reduce((sum, r) => sum + Number(r.payment_count), 0)}
                                            </td>
                                            <td className="py-2 text-right font-bold text-emerald-600">
                                                {new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(totalRevenue)}
                                            </td>
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
