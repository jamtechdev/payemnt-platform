import EntityListCard from '@/components/admin/EntityListCard';
import AdminLayout from '@/layouts/AdminLayout';

type LooseRecord = Record<string, unknown>;

function asRecord(input: unknown): LooseRecord {
    if (input && typeof input === 'object') return input as LooseRecord;
    return {};
}

export default function AuditLogDetail({ log, diff }: { log: unknown; diff: unknown }) {
    const logData = asRecord(log);
    const diffData = asRecord(diff);

    return (
        <AdminLayout title="Audit log detail">
            <div className="grid gap-4 lg:grid-cols-2">
                <EntityListCard
                    title="Log entry"
                    emptyText="No log data."
                    items={Object.entries(logData).map(([key, value]) => ({
                        key,
                        content: (
                            <div className="flex items-center justify-between gap-2">
                                <span className="font-medium text-slate-700">{key}</span>
                                <span className="max-w-[65%] truncate text-sm text-slate-600">{typeof value === 'string' ? value : JSON.stringify(value)}</span>
                            </div>
                        ),
                    }))}
                />
                <EntityListCard
                    title="Changed values"
                    emptyText="No difference data."
                    items={Object.entries(diffData).map(([key, value]) => ({
                        key,
                        content: (
                            <div className="flex items-center justify-between gap-2">
                                <span className="font-medium text-slate-700">{key}</span>
                                <span className="max-w-[65%] truncate text-sm text-slate-600">{typeof value === 'string' ? value : JSON.stringify(value)}</span>
                            </div>
                        ),
                    }))}
                />
            </div>
        </AdminLayout>
    );
}
