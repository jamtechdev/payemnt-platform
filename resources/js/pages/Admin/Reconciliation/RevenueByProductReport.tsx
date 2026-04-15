import AdminLayout from '@/layouts/AdminLayout';
import EntityListCard from '@/components/admin/EntityListCard';

type LooseRecord = Record<string, unknown>;

function asArray(input: unknown): LooseRecord[] {
    if (Array.isArray(input)) return input as LooseRecord[];
    return [];
}

export default function RevenueByProductReport({ rows }: { rows: unknown }) {
    const data = asArray(rows);

    return (
        <AdminLayout title="Revenue by product">
            <EntityListCard
                title="Revenue summary"
                emptyText="No revenue data available."
                items={data.map((row, idx) => ({
                    key: String(row.product_id ?? idx),
                    content: (
                        <div className="flex items-center justify-between">
                            <span className="font-medium text-slate-800">Product #{String(row.product_id ?? '-')}</span>
                            <span className="text-emerald-600">{String(row.total ?? 0)}</span>
                        </div>
                    ),
                }))}
            />
        </AdminLayout>
    );
}
