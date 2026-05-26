import AdminLayout from '@/layouts/AdminLayout';
import DataTable from '@/components/shared/DataTable';
import { createColumnHelper } from '@tanstack/react-table';
import { router } from '@inertiajs/react';
import { Eye, Search } from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';
import ActionBtn from '@/components/shared/ActionBtn';
import { useState } from 'react';

type LooseRecord = Record<string, unknown>;

interface PaginatedLogs {
    data: LooseRecord[];
    current_page: number;
    last_page: number;
    from: number;
    to: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

function formatAction(action: string): string {
    return action.replaceAll('_', ' ').replaceAll('-', ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

function formatEntityType(type: string): string {
    const parts = type.split('\\');
    return parts[parts.length - 1] ?? type;
}

export default function AuditLog({ logs }: { logs: PaginatedLogs }) {
    const rows = logs?.data ?? [];
    const [search, setSearch] = useState('');

    const columnHelper = createColumnHelper<LooseRecord>();

    const filteredRows = search
        ? rows.filter(
              (r) =>
                  String(r.action ?? '').toLowerCase().includes(search.toLowerCase()) ||
                  formatEntityType(String(r.entity_type ?? '')).toLowerCase().includes(search.toLowerCase()) ||
                  String((r.actor as Record<string, string> | undefined)?.name ?? '').toLowerCase().includes(search.toLowerCase()),
          )
        : rows;

    const columns: any[] = [
        columnHelper.accessor((row) => String(row.occurred_at || row.created_at || ''), {
            id: 'date',
            header: 'Date/Time',
            cell: (info) => {
                const d = new Date(info.getValue());
                return isNaN(d.getTime()) ? '-' : d.toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
            },
        }),
        columnHelper.accessor((row) => formatAction(String(row.action ?? '')) as string, {
            id: 'action',
            header: 'Action',
            cell: (info) => <span className="font-medium">{info.getValue()}</span>,
        }),
        columnHelper.accessor((row) => formatEntityType(String(row.entity_type ?? '')) as string, {
            id: 'entity_type',
            header: 'Type',
            cell: (info) => <span className="rounded bg-slate-100 px-2 py-0.5 text-xs font-mono text-slate-600">{info.getValue()}</span>,
        }),
        columnHelper.accessor((row) => String(row.entity_id ?? '-'), {
            id: 'entity_id',
            header: 'Entity ID',
            cell: (info) => <span className="font-mono text-xs">{info.getValue()}</span>,
        }),
        columnHelper.accessor((row) => String(((row.actor as Record<string, string | undefined>) || {}).name ?? 'System'), {
            id: 'actor',
            header: 'By',
        }),
        columnHelper.display({
            id: 'actions',
            header: 'Actions',
            cell: (info) => (
                <ActionBtn tone="muted" href={route('admin.audit-logs.show', Number(info.row.original.id))} title="View">
                    <Eye className="h-3.5 w-3.5" /> View
                </ActionBtn>
            ),
        }),
    ];

    return (
        <AdminLayout title="Audit logs">
            <Card className="mb-4">
                <CardContent className="pt-4">
                    <div className="relative max-w-sm">
                        <Search className="absolute top-2.5 left-3 h-4 w-4 text-muted-foreground" />
                        <input
                            type="text"
                            className="w-full rounded-md border border-input bg-background py-2 pr-3 pl-9 text-sm"
                            placeholder="Search action, type, user..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                        />
                    </div>
                </CardContent>
            </Card>

            <DataTable columns={columns} data={filteredRows} showHeader showRowCount stripedRows clickableRows={false} emptyMessage="No audit logs found." stickyHeader compact />

            {logs.last_page > 1 && (
                <div className="mt-4 flex items-center justify-between text-sm text-muted-foreground">
                    <span>Showing {logs.from ?? 0}–{logs.to ?? 0} of {logs.total ?? 0}</span>
                    <div className="flex gap-1">
                        {logs.links.map((link, i) => (
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
            )}
        </AdminLayout>
    );
}
