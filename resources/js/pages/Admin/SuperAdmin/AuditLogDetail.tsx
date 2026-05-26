import AdminLayout from '@/layouts/AdminLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { Button } from '@/components/ui/button';

interface AuditLogData {
    id: number;
    action: string;
    entity_type: string;
    entity_id: number | string | null;
    actor?: { id: number; name: string } | null;
    ip_address: string | null;
    user_agent: string | null;
    changes: { old?: Record<string, unknown>; new?: Record<string, unknown> } | null;
    occurred_at: string;
    created_at: string;
}

function formatAction(action: string): string {
    return action.replaceAll('_', ' ').replaceAll('-', ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

function formatEntityType(type: string): string {
    const parts = type.split('\\');
    return parts[parts.length - 1] ?? type;
}

export default function AuditLogDetail({ log, diff }: { log: AuditLogData; diff: Record<string, unknown> }) {
    const changes = log.changes;

    return (
        <AdminLayout title="Audit log detail">
            <div className="mb-4">
                <Link href={route('admin.audit-logs.index')}>
                    <Button variant="outline" size="sm">
                        <ArrowLeft className="mr-1 h-4 w-4" /> Back to logs
                    </Button>
                </Link>
            </div>

            <div className="grid gap-4 md:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Overview</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3 text-sm">
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">Action</span>
                            <span className="font-medium">{formatAction(log.action)}</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">Type</span>
                            <span className="rounded bg-slate-100 px-2 py-0.5 font-mono text-xs">{formatEntityType(log.entity_type)}</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">Entity ID</span>
                            <span className="font-mono text-xs">{log.entity_id ?? '-'}</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">By</span>
                            <span>{log.actor?.name ?? 'System'}</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">IP address</span>
                            <span className="font-mono text-xs">{log.ip_address ?? '-'}</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">Date/Time</span>
                            <span>{new Date(log.occurred_at || log.created_at).toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })}</span>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Changes</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {!changes ? (
                            <p className="text-sm text-muted-foreground">No change data recorded.</p>
                        ) : (
                            <div className="space-y-4 text-sm">
                                {changes.old && Object.keys(changes.old).length > 0 && (
                                    <div>
                                        <p className="mb-1 text-xs font-medium text-muted-foreground">Old values</p>
                                        <pre className="overflow-auto rounded bg-slate-50 p-2 text-xs text-slate-600 max-h-40">
                                            {JSON.stringify(changes.old, null, 2)}
                                        </pre>
                                    </div>
                                )}
                                {changes.new && Object.keys(changes.new).length > 0 && (
                                    <div>
                                        <p className="mb-1 text-xs font-medium text-muted-foreground">New values</p>
                                        <pre className="overflow-auto rounded bg-emerald-50 p-2 text-xs text-emerald-800 max-h-40">
                                            {JSON.stringify(changes.new, null, 2)}
                                        </pre>
                                    </div>
                                )}
                                {(!changes.old || Object.keys(changes.old).length === 0) &&
                                    (!changes.new || Object.keys(changes.new).length === 0) && (
                                    <p className="text-sm text-muted-foreground">No change data recorded.</p>
                                )}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>

            {log.user_agent && (
                <Card className="mt-4">
                    <CardHeader>
                        <CardTitle className="text-base">User agent</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p className="break-all text-sm text-muted-foreground">{log.user_agent}</p>
                    </CardContent>
                </Card>
            )}
        </AdminLayout>
    );
}
