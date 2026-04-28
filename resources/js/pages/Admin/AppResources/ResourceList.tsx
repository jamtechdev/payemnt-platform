import AdminLayout from '@/layouts/AdminLayout';
import DataTable from '@/components/shared/DataTable';
import StatusBadge from '@/components/shared/StatusBadge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { createColumnHelper } from '@tanstack/react-table';
import { router } from '@inertiajs/react';
import { useState } from 'react';
import { Search } from 'lucide-react';

interface ResourceRow {
    id: number;
    name: string;
    status: string;
    created_at?: string;
    partner?: { name?: string };
}

interface PaginatedItems {
    data: ResourceRow[];
    current_page: number;
    last_page: number;
    from: number;
    to: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

interface Props {
    title: string;
    items: PaginatedItems;
    filters?: { search?: string };
    routeName: string;
}

export default function ResourceList({ title, items, filters, routeName }: Props) {
    const rows = items?.data ?? [];
    const [search, setSearch] = useState(filters?.search ?? '');

    const applyFilters = () => {
        router.get(route(routeName), { search }, { preserveState: true });
    };

    const columnHelper = createColumnHelper<ResourceRow>();
    const columns = [
        columnHelper.accessor('name', { header: 'Name' }),
        columnHelper.accessor((row) => row.partner?.name ?? '-', { id: 'partner', header: 'Partner' }),
        columnHelper.accessor('status', {
            header: 'Status',
            cell: (info) => <StatusBadge status={info.getValue()} type="customer" />,
        }),
        columnHelper.accessor((row) => row.created_at ?? '-', {
            id: 'created_at',
            header: 'Created At',
            cell: (info) => {
                const v = info.getValue();
                if (!v || v === '-') return '-';
                const d = new Date(v);
                return isNaN(d.getTime()) ? v : d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
            },
        }),
    ];

    return (
        <AdminLayout title={title}>
            <Card className="mb-4">
                <CardContent className="pt-4">
                    <div className="flex gap-3">
                        <div className="relative flex-1">
                            <Search className="absolute top-2.5 left-3 h-4 w-4 text-muted-foreground" />
                            <input
                                type="text"
                                className="w-full rounded-md border border-input bg-background py-2 pr-3 pl-9 text-sm"
                                placeholder={`Search ${title.toLowerCase()}...`}
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                onKeyDown={(e) => e.key === 'Enter' && applyFilters()}
                            />
                        </div>
                        <Button onClick={applyFilters}>Search</Button>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader><CardTitle className="text-base">{title}</CardTitle></CardHeader>
                <CardContent>
                    <DataTable columns={columns} data={rows} showHeader showRowCount stripedRows clickableRows={false} emptyMessage={`No ${title.toLowerCase()} found.`} stickyHeader compact />
                    <div className="mt-4 flex items-center justify-between text-sm text-muted-foreground">
                        <span>Showing {items.from ?? 0}–{items.to ?? 0} of {items.total ?? 0}</span>
                        <div className="flex gap-1">
                            {items.links.map((link, i) => (
                                <button
                                    key={i}
                                    disabled={!link.url}
                                    onClick={() => link.url && router.get(link.url, {}, { preserveState: true })}
                                    className={`rounded px-3 py-1 text-xs border ${
                                        link.active
                                            ? 'bg-primary text-primary-foreground border-primary'
                                            : 'border-input hover:bg-accent disabled:opacity-40'
                                    }`}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ))}
                        </div>
                    </div>
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
