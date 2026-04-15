import AdminLayout from '@/layouts/AdminLayout';
import EntityListCard from '@/components/admin/EntityListCard';

type LooseRecord = Record<string, unknown>;

function asArray(input: unknown): LooseRecord[] {
    if (Array.isArray(input)) return input as LooseRecord[];
    return [];
}

export default function Settings({ settings }: { settings: unknown }) {
    const rows = asArray(settings);

    return (
        <AdminLayout title="Settings">
            <EntityListCard
                title="System settings"
                emptyText="No settings configured."
                items={rows.map((row, idx) => ({
                    key: String(row.id ?? idx),
                    content: (
                        <div className="grid grid-cols-1 gap-2 md:grid-cols-3">
                            <p className="font-medium text-slate-700">{String(row.key ?? '-')}</p>
                            <p className="md:col-span-2 break-words text-sm text-slate-600">
                                {typeof row.value === 'string' ? row.value : JSON.stringify(row.value)}
                            </p>
                        </div>
                    ),
                }))}
            />
        </AdminLayout>
    );
}
