import AdminLayout from '@/layouts/AdminLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { router } from '@inertiajs/react';
import { Line, LineChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';
import { useEffect } from 'react';

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

export default function PartnerPerformance({ series = [], months = 12 }: { series?: PartnerSeries[]; months?: number }) {
    useEffect(() => {
        const intervalId = window.setInterval(() => {
            router.reload({
                only: ['series'],
                preserveScroll: true,
                preserveState: true,
            });
        }, 10000);

        return () => window.clearInterval(intervalId);
    }, []);

    return (
        <AdminLayout title="Partner Performance">
            <div className="space-y-4">
                <Card>
                    <CardHeader>
                        <CardTitle>Partner growth/loss trend (last {months} months)</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {series.length === 0 ? (
                            <p className="text-sm text-muted-foreground">No performance data available.</p>
                        ) : (
                            <div className="space-y-4">
                                {series.map((partner) => (
                                    <div key={partner.partner_id} className="rounded-lg border border-border p-4">
                                        <div className="mb-2 flex items-center justify-between">
                                            <h3 className="font-medium">{partner.partner_name}</h3>
                                            <span className={partner.trend === 'growth' ? 'text-sm font-semibold text-green-600' : 'text-sm font-semibold text-red-500'}>
                                                {partner.trend === 'growth' ? '+' : ''}{partner.growth_delta} customers
                                            </span>
                                        </div>
                                        <div className="h-52">
                                            <ResponsiveContainer width="100%" height="100%">
                                                <LineChart data={partner.points}>
                                                    <XAxis dataKey="bucket" />
                                                    <YAxis />
                                                    <Tooltip />
                                                    <Line type="monotone" dataKey="customer_count" stroke="#0e9f84" strokeWidth={2} name="Customers" />
                                                </LineChart>
                                            </ResponsiveContainer>
                                        </div>
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
