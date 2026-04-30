import AdminLayout from '@/layouts/AdminLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

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

interface OptionItem {
    id: number;
    name: string;
}

export default function RevenueByProductReport({
    rows,
    filters,
    partners = [],
    products = [],
}: {
    rows: RevenueRow[];
    filters?: Filters;
    partners?: OptionItem[];
    products?: OptionItem[];
}) {
    const [form, setForm] = useState<Filters>(filters ?? {});

    useEffect(() => {
        const intervalId = window.setInterval(() => {
            router.reload({
                only: ['rows'],
                preserveScroll: true,
                preserveState: true,
            });
        }, 9000);

        return () => window.clearInterval(intervalId);
    }, []);

    const applyFilters = () => {
        router.get(route('admin.reports.revenue'), form as Record<string, string>, { preserveState: true });
    };

    const totalRevenue = rows.reduce((sum, r) => sum + Number(r.expected_revenue), 0);

    return (
        <AdminLayout title="Revenue by product">
            <div className="space-y-4">
                {/* BRD REC-004: Time period filtering */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Filters</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-3 md:grid-cols-6">
                            <select
                                className="rounded-md border border-input bg-background px-3 py-2 text-sm"
                                value={form.period ?? 'monthly'}
                                onChange={(e) => setForm({ ...form, period: e.target.value })}
                            >
                                <option value="daily">Daily</option>
                                <option value="monthly">Monthly</option>
                                <option value="yearly">Yearly</option>
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
                            <select className="rounded-md border border-input bg-background px-3 py-2 text-sm" value={form.partner_id ?? ''} onChange={(e) => setForm({ ...form, partner_id: e.target.value })}>
                                <option value="">All partners</option>
                                {partners.map((partner) => <option key={partner.id} value={String(partner.id)}>{partner.name}</option>)}
                            </select>
                            <select className="rounded-md border border-input bg-background px-3 py-2 text-sm" value={form.product_id ?? ''} onChange={(e) => setForm({ ...form, product_id: e.target.value })}>
                                <option value="">All products</option>
                                {products.map((product) => <option key={product.id} value={String(product.id)}>{product.name}</option>)}
                            </select>
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
                            <CardTitle className="text-base">Expected revenue by partner/product</CardTitle>
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
                                            <th className="py-2 text-left font-medium">Partner</th>
                                            <th className="py-2 text-left font-medium">Period</th>
                                            <th className="py-2 text-right font-medium">Customers</th>
                                            <th className="py-2 text-right font-medium">Guide Price</th>
                                            <th className="py-2 text-right font-medium">Expected Revenue</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {rows.map((row, index) => (
                                            <tr key={`${row.product_id}-${row.partner_id}-${row.bucket}-${index}`} className="border-b border-border/60 hover:bg-muted/30">
                                                <td className="py-2 font-medium text-foreground">{row.product_name}</td>
                                                <td className="py-2 text-foreground">{row.partner_name}</td>
                                                <td className="py-2 text-foreground">{row.bucket}</td>
                                                <td className="py-2 text-right text-foreground">{row.customer_count}</td>
                                                <td className="py-2 text-right text-foreground">
                                                    {new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(Number(row.guide_price))}
                                                </td>
                                                <td className="py-2 text-right font-semibold text-emerald-600">
                                                    {new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(Number(row.expected_revenue))}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                    <tfoot>
                                        <tr className="border-t-2 border-border">
                                            <td className="py-2 font-bold text-foreground">Total</td>
                                            <td></td>
                                            <td></td>
                                            <td className="py-2 text-right font-bold text-foreground">
                                                {rows.reduce((sum, r) => sum + Number(r.customer_count), 0)}
                                            </td>
                                            <td></td>
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
