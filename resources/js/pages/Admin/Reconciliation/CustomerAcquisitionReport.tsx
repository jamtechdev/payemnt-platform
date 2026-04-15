import EntityListCard from '@/components/admin/EntityListCard';
import AdminLayout from '@/layouts/AdminLayout';

type LooseRecord = Record<string, unknown>;

function asArray(input: unknown): LooseRecord[] {
    if (Array.isArray(input)) return input as LooseRecord[];
    return [];
}

export default function CustomerAcquisitionReport({ rows }: { rows: unknown }) {
    const data = asArray(rows);

    return (
        <AdminLayout title="Customer acquisition">
            <EntityListCard
                title="Acquisition by product and period"
                emptyText="No acquisition data available."
                items={data.map((row, idx) => ({
                    key: `${String(row.product_id ?? idx)}-${String(row.bucket ?? idx)}`,
                    content: (
                        <div className="flex items-center justify-between gap-2">
                            <span className="text-sm text-slate-700">
                                Product #{String(row.product_id ?? '-')} | {String(row.bucket ?? '-')}
                            </span>
                            <span className="font-semibold text-emerald-600">{String(row.total ?? 0)}</span>
                        </div>
                    ),
                }))}
            />
        </AdminLayout>
    );
}
