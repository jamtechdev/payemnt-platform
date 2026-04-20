import AdminLayout from '@/layouts/AdminLayout';
import MetricCard from '@/components/admin/MetricCard';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { BarChart2, DollarSign } from 'lucide-react';

interface ProductCount {
    product_id: number;
    product_name: string;
    total: number;
}

interface ProductRevenue {
    product_id: number;
    total_revenue: number;
}

interface ReconciliationDashboardProps {
    monthlyCustomers: number;
    monthlyRevenue: number;
    customersByProduct: ProductCount[];
    revenueByProduct: ProductRevenue[];
}

export default function ReconciliationDashboard(props: ReconciliationDashboardProps) {
    return (
        <AdminLayout title="Reconciliation dashboard">
            <div className="space-y-6">
                {/* BRD REC-001/REC-003: Summary metrics */}
                <div className="grid gap-4 md:grid-cols-2">
                    <MetricCard label="Monthly customers" value={props.monthlyCustomers} />
                    <MetricCard
                        label="Monthly revenue"
                        value={new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(props.monthlyRevenue)}
                        valueClassName="text-emerald-600"
                    />
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    {/* BRD REC-001: Customer count per product */}
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
                                            <span className="rounded-full bg-blue-100 px-3 py-0.5 text-sm font-semibold text-blue-700 dark:bg-blue-500/20 dark:text-blue-300">
                                                {row.total}
                                            </span>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* BRD REC-003: Income per product line */}
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
                                        <div key={row.product_id} className="flex items-center justify-between rounded-lg border border-border px-4 py-2">
                                            <span className="text-sm font-medium text-foreground">Product #{row.product_id}</span>
                                            <span className="rounded-full bg-emerald-100 px-3 py-0.5 text-sm font-semibold text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300">
                                                {new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(Number(row.total_revenue))}
                                            </span>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AdminLayout>
    );
}
