import EntityListCard from '@/components/admin/EntityListCard';
import AdminLayout from '@/layouts/AdminLayout';

type LooseRecord = Record<string, unknown>;

function asArray(input: unknown): LooseRecord[] {
    if (Array.isArray(input)) return input as LooseRecord[];
    if (input && typeof input === 'object' && Array.isArray((input as { data?: unknown }).data)) {
        return (input as { data: LooseRecord[] }).data;
    }
    return [];
}

export default function AuditLog({ logs }: { logs: unknown }) {
    const rows = asArray(logs);

    return (
        <AdminLayout title="Audit logs">
            <EntityListCard
                title="System events"
                emptyText="No audit logs available."
                items={rows.map((row, idx) => ({
                    key: String(row.id ?? idx),
                    content: (
                        <div className="space-y-1 text-sm">
                            <p className="font-medium text-slate-900  dark:text-white">{String(row.action ?? 'event')}</p>
                            <p className="text-slate-500">
                                {String(row.model_type ?? '-')} #{String(row.model_id ?? '-')}
                            </p>
                        </div>
                    ),
                }))}
            />
        </AdminLayout>
    );
}
