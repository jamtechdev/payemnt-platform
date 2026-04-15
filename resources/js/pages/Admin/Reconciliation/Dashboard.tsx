import AdminLayout from '@/layouts/AdminLayout';
import MetricCard from '@/components/admin/MetricCard';

interface ReconciliationDashboardProps {
    monthlyCustomers: number;
    monthlyRevenue: number;
}

export default function ReconciliationDashboard(props: ReconciliationDashboardProps) {
    return (
        <AdminLayout title="Reconciliation dashboard">
            <div className="grid gap-4 md:grid-cols-2">
                <MetricCard label="Monthly customers" value={props.monthlyCustomers} />
                <MetricCard label="Monthly revenue" value={props.monthlyRevenue.toLocaleString()} valueClassName="text-emerald-600" />
            </div>
        </AdminLayout>
    );
}
