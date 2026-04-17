import AdminLayout from '@/layouts/AdminLayout';
import EntityListCard from '@/components/admin/EntityListCard';

type LooseRecord = Record<string, unknown>;

function asArray(input: unknown): LooseRecord[] {
    if (Array.isArray(input)) return input as LooseRecord[];
    if (input && typeof input === 'object' && Array.isArray((input as { data?: unknown }).data)) {
        return (input as { data: LooseRecord[] }).data;
    }
    return [];
}

export default function ProductVersions({ product, versions }: { product: LooseRecord; versions: unknown }) {
    const rows = asArray(versions);

    return (
        <AdminLayout title={`Product versions - ${String(product.name ?? '')}`}>
            <EntityListCard
                title="Version history"
                emptyText="No version snapshots available."
                items={rows.map((row, idx) => ({
                    key: String(row.id ?? idx),
                    content: (
                        <div className="flex items-center justify-between gap-3">
                            <div>
                                <p className="font-medium text-slate-900  dark:text-white">Version #{String(row.version_number ?? '-')}</p>
                                <p className="text-xs text-slate-500">Created at {String(row.created_at ?? '-')}</p>
                            </div>
                            <span className="text-xs text-slate-600">Snapshot saved</span>
                        </div>
                    ),
                }))}
            />
        </AdminLayout>
    );
}
