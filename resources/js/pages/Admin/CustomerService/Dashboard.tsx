import AdminLayout from '@/layouts/AdminLayout';
import MetricCard from '@/components/admin/MetricCard';

interface CustomerServiceDashboardProps {
    totalCustomers: number;
    activeCustomers: number;
}

export default function Dashboard(props: CustomerServiceDashboardProps) {
    const inactiveCustomers = Math.max(props.totalCustomers - props.activeCustomers, 0);

    return (
        <AdminLayout title="Customer service dashboard">
            <div className="grid gap-4 md:grid-cols-3">
                <MetricCard label="Total customers" value={props.totalCustomers} />
                <MetricCard label="Active customers" value={props.activeCustomers} valueClassName="text-emerald-600" />
                <MetricCard label="Needs follow-up" value={inactiveCustomers} valueClassName="text-amber-600" />
            </div>
        </AdminLayout>
    );
}
