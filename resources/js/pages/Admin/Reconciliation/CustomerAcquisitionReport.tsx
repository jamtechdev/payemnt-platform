import AdminLayout from '@/layouts/AdminLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { router } from '@inertiajs/react';
import { useState } from 'react';

interface AcquisitionRow {
    product_id: number;
    product_name: string;
    partner_id: number;
    partner_name: string;
    bucket: string;
    total: number;
}

interface Filters {
    period?: string;
    date_from?: string;
    date_to?: string;
    partner_id?: string;
    product_id?: string;
}

export default function CustomerAcquisitionReport({ rows, filters }: { rows: AcquisitionRow[]; filters: Filters }) {
    const [form, setForm] = useState<Filters>(filters ?? {});

    const applyFilters = () => {
        router.get(route('admin.reports.customer-acquisition'), form as Record<string, string>, { preserveState: true });
    };

    return (
        <AdminLayout title="Customer acquisition">
            <div className="space-y-4">
                {/* BRD REC-004: Filters */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Filters</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-3 md:grid-cols-5">
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
                                placeholder="From date"
                            />
                            <input
                                type="date"
                                className="rounded-md border border-input bg-background px-3 py-2 text-sm"
                                value={form.date_to ?? ''}
                                onChange={(e) => setForm({ ...form, date_to: e.target.value })}
                                placeholder="To date"
                            />
                            <input
                                type="text"
                                className="rounded-md border border-input bg-background px-3 py-2 text-sm"
                                value={form.partner_id ?? ''}
                                onChange={(e) => setForm({ ...form, partner_id: e.target.value })}
                                placeholder="Partner ID"
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

                {/* BRD REC-001: Acquisition data */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Acquisition by product, partner and period</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {rows.length === 0 ? (
                            <p className="text-sm text-muted-foreground">No acquisition data available.</p>
                        ) : (
                            <div className="overflow-auto">
                                <table className="w-full min-w-[600px] text-sm">
                                    <thead>
                                        <tr className="border-b border-border text-muted-foreground">
                                            <th className="py-2 text-left font-medium">Period</th>
                                            <th className="py-2 text-left font-medium">Product</th>
                                            <th className="py-2 text-left font-medium">Partner</th>
                                            <th className="py-2 text-right font-medium">Count</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {rows.map((row, idx) => (
                                            <tr key={idx} className="border-b border-border/60 hover:bg-muted/30">
                                                <td className="py-2 font-mono text-foreground">{row.bucket}</td>
                                                <td className="py-2 text-foreground">{row.product_name}</td>
                                                <td className="py-2 text-foreground">{row.partner_name}</td>
                                                <td className="py-2 text-right font-semibold text-emerald-600">{row.total}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                    <tfoot>
                                        <tr className="border-t border-border">
                                            <td colSpan={3} className="py-2 font-medium text-foreground">Total</td>
                                            <td className="py-2 text-right font-bold text-emerald-600">
                                                {rows.reduce((sum, r) => sum + Number(r.total), 0)}
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
