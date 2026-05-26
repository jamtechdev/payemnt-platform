import AdminLayout from '@/layouts/AdminLayout';
import DataTable from '@/components/shared/DataTable';
import { createColumnHelper } from '@tanstack/react-table';
import { router, Link } from '@inertiajs/react';
import { Eye } from 'lucide-react';
import { Button } from '@/components/ui/button';

interface AuditLogRow {
    id: number;
    action: string;
    entity_type: string;
    entity_id: number | string | null;
    actor: { id: number; name: string } | null;
    created_at: string;
}

interface PaginatedLogs {
    data: AuditLogRow[];
    current_page: number;
    last_page: number;
    from: number;
    to: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

interface Props {
    logs: PaginatedLogs;
}

const ACTION_LABELS: Record<string, string> = {
    created: 'Created',
    updated: 'Updated',
    deleted: 'Deleted',
    login: 'Login',
    logout: 'Logout',
    api_key_generated: 'API key generated',
    api_key_revoked: 'API key revoked',
};

const ACTION_COLORS: Record<string, string> = {
    created: 'bg-emerald-100 text-emerald-700 ring-emerald-200',
    updated: 'bg-blue-100 text-blue-700 ring-blue-200',
    deleted: 'bg-red-100 text-red-700 ring-red-200',
    login: 'bg-violet-100 text-violet-700 ring-violet-200',
    logout: 'bg-slate-100 text-slate-600 ring-slate-200',
    api_key_generated: 'bg-amber-100 text-amber-700 ring-amber-200',
    api_key_revoked: 'bg-orange-100 text-orange-700 ring-orange-200',
};

function actionBadge(action: string) {
    const label = ACTION_LABELS[action] ?? action.replaceAll('_', ' ');
    return (
        <span className={`inline-block rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset ${ACTION_COLORS[action] || 'bg-slate-100 text-slate-600 ring-slate-200'}`}>
            {label}
        </span>
    );
}

export default function PartnerAuditLog({ logs }: Props) {
    const rows = logs?.data ?? [];

    const columnHelper = createColumnHelper<AuditLogRow>();

    const columns = [
        columnHelper.accessor((row) => row.created_at, {
            id: 'created_at', header: 'Date / Time',
            cell: (info) => <span className="text-xs text-muted-foreground">{info.getValue()}</span>,
        }),
        columnHelper.accessor((row) => row.action, {
            id: 'action', header: 'Action',
            cell: (info) => actionBadge(info.getValue()),
        }),
        columnHelper.accessor((row) => row.entity_type, {
            id: 'entity_type', header: 'Type',
            cell: (info) => {
                const type = info.getValue();
                const short = type.split('\\').pop() ?? type;
                return (
                    <span className="inline-block rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600 ring-1 ring-inset ring-slate-200">
                        {short}
                    </span>
                );
            },
        }),
        columnHelper.accessor((row) => row.entity_id, {
            id: 'entity_id', header: 'Entity ID',
            cell: (info) => <span className="font-mono text-xs">{info.getValue() ?? '-'}</span>,
        }),
        columnHelper.accessor((row) => row.actor?.name ?? '-', {
            id: 'actor', header: 'Actor',
            cell: (info) => <span className="text-xs">{info.getValue()}</span>,
        }),
        columnHelper.display({
            id: 'actions', header: 'View',
            cell: (info) => (
                <Link href={route('admin.audit-logs.show', info.row.original.id)}>
                    <Button size="sm" variant="ghost">
                        <Eye className="h-3.5 w-3.5" />
                    </Button>
                </Link>
            ),
        }),
    ];

    return (
        <AdminLayout title="My audit logs">
            <div className="space-y-4">
                <div>
                    <h1 className="text-2xl font-bold text-gray-800">Audit Logs</h1>
                    <p className="mt-1 text-sm text-muted-foreground">Activities related to your account.</p>
                </div>

                <DataTable columns={columns} data={rows} showHeader showRowCount stripedRows clickableRows={false} emptyMessage="No audit logs found." stickyHeader compact />

                {logs.last_page > 1 && (
                    <div className="flex items-center justify-between text-sm text-muted-foreground">
                        <span>Showing {logs.from}–{logs.to} of {logs.total}</span>
                        <div className="flex gap-1">
                            {logs.links.map((link, i) => (
                                <button key={i} disabled={!link.url}
                                    onClick={() => link.url && router.get(link.url, {}, { preserveState: true })}
                                    className={`rounded px-3 py-1 text-xs border ${link.active ? 'bg-primary text-primary-foreground border-primary' : 'border-input hover:bg-accent disabled:opacity-40'}`}
                                    dangerouslySetInnerHTML={{ __html: link.label }} />
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}
