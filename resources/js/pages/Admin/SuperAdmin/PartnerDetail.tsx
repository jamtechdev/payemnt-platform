import EntityListCard from '@/components/admin/EntityListCard';
import AdminLayout from '@/layouts/AdminLayout';

type LooseRecord = Record<string, unknown>;

function asRecord(input: unknown): LooseRecord {
    if (input && typeof input === 'object') return input as LooseRecord;
    return {};
}

export default function PartnerDetail({ partner }: { partner: unknown }) {
    const model = asRecord(partner);

    return (
        <AdminLayout title="Partner detail">
            <EntityListCard
                title="Partner information"
                emptyText="No partner data."
                items={Object.entries(model).map(([key, value]) => ({
                    key,
                    content: (
                        <div className="flex items-center justify-between gap-2">
                            <span className="font-medium text-slate-700">{key}</span>
                            <span className="max-w-[65%] truncate text-sm text-slate-600">{typeof value === 'string' ? value : JSON.stringify(value)}</span>
                        </div>
                    ),
                }))}
            />
        </AdminLayout>
    );
}
