import AdminLayout from '@/layouts/AdminLayout';
import DataTable from '@/components/shared/DataTable';
import StatusBadge from '@/components/shared/StatusBadge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { createColumnHelper } from '@tanstack/react-table';
import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { Eye, Search } from 'lucide-react';
import ActionBtn from '@/components/shared/ActionBtn';

interface CategoryRow {
    connect_categories_id: number;
    category_code?: string;
    name?: string;
    partner_code?: string;
    icon_url?: string;
    status?: string;
    from_platform?: number;
    created_at?: string;
}

interface Paginated {
    data: CategoryRow[];
    current_page: number;
    last_page: number;
    from: number;
    to: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

interface Filters { search?: string; status?: string; }

function fmtDate(value: unknown): string {
    if (!value) return '—';
    const d = new Date(String(value));
    return isNaN(d.getTime()) ? String(value) : d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

export default function ConnectCategoryList({ categories, filters }: { categories: Paginated; filters?: Filters }) {
    const rows = categories?.data ?? [];
    const [form, setForm] = useState<Filters>(filters ?? {});

    const applyFilters = () => {
        router.get(route('admin.connect-categories.index'), form as Record<string, string>, { preserveState: true });
    };

    const columnHelper = createColumnHelper<CategoryRow>();
    const columns = [
        columnHelper.accessor((r) => r.category_code ?? '—', { id: 'category_code', header: 'Category Code' }),
        columnHelper.accessor((r) => r.name ?? '—', { id: 'name', header: 'Name' }),
        columnHelper.accessor((r) => r.partner_code ?? '—', { id: 'partner_code', header: 'Partner Code' }),
        columnHelper.accessor((r) => r.from_platform ?? 0, {
            id: 'from_platform',
            header: 'From Platform',
            cell: (info) => (
                <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${info.getValue() ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600'}`}>
                    {info.getValue() ? 'Yes' : 'No'}
                </span>
            ),
        }),
        columnHelper.accessor((r) => r.created_at ?? '—', {
            id: 'created_at', header: 'Created At',
            cell: (info) => fmtDate(info.getValue()),
        }),
        columnHelper.accessor((r) => r.status ?? '—', {
            id: 'status', header: 'Status',
            cell: (info) => <StatusBadge status={info.getValue()} type="customer" />,
        }),
        columnHelper.display({
            id: 'actions', header: 'Actions',
            cell: (info) => (
                <ActionBtn tone="primary" href={route('admin.connect-categories.show', info.row.original.connect_categories_id)} title="View">
                    <Eye className="h-3.5 w-3.5" /> View
                </ActionBtn>
            ),
        }),
    ];

    return (
        <AdminLayout title="Connect Categories">
            <Card className="mb-4">
                <CardContent className="pt-4">
                    <div className="grid gap-3 md:grid-cols-4">
                        <div className="relative md:col-span-2">
                            <Search className="absolute top-2.5 left-3 h-4 w-4 text-muted-foreground" />
                            <input
                                type="text"
                                className="w-full rounded-md border border-input bg-background py-2 pr-3 pl-9 text-sm"
                                placeholder="Search name, code, partner..."
                                value={form.search ?? ''}
                                onChange={(e) => setForm({ ...form, search: e.target.value })}
                                onKeyDown={(e) => e.key === 'Enter' && applyFilters()}
                            />
                        </div>
                        <select
                            className="rounded-md border border-input bg-background px-3 py-2 text-sm"
                            value={form.status ?? ''}
                            onChange={(e) => setForm({ ...form, status: e.target.value })}
                        >
                            <option value="">All statuses</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        <Button onClick={applyFilters}>Search</Button>
                    </div>
                </CardContent>
            </Card>

            <div className="mb-4 grid gap-4 md:grid-cols-3">
                <Card className="border-blue-200/70 bg-blue-50/50">
                    <CardHeader className="pb-2"><CardTitle className="text-base">Total</CardTitle></CardHeader>
                    <CardContent className="text-2xl font-semibold">{categories.total ?? 0}</CardContent>
                </Card>
                <Card className="border-emerald-200/70 bg-emerald-50/50">
                    <CardHeader className="pb-2"><CardTitle className="text-base">Active</CardTitle></CardHeader>
                    <CardContent className="text-2xl font-semibold text-emerald-600">
                        {rows.filter((r) => r.status === 'active').length}
                    </CardContent>
                </Card>
                <Card className="border-rose-200/70 bg-rose-50/50">
                    <CardHeader className="pb-2"><CardTitle className="text-base">Inactive</CardTitle></CardHeader>
                    <CardContent className="text-2xl font-semibold text-rose-600">
                        {rows.filter((r) => r.status === 'inactive').length}
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader><CardTitle className="text-base">Connect Category Records</CardTitle></CardHeader>
                <CardContent>
                    <DataTable columns={columns} data={rows} showHeader showRowCount stripedRows clickableRows={false} emptyMessage="No connect categories found." stickyHeader compact />
                    <div className="mt-4 flex items-center justify-between text-sm text-muted-foreground">
                        <span>Showing {categories.from ?? 0}–{categories.to ?? 0} of {categories.total ?? 0}</span>
                        <div className="flex gap-1">
                            {categories.links.map((link, i) => (
                                <button
                                    key={i}
                                    disabled={!link.url}
                                    onClick={() => link.url && router.get(link.url, {}, { preserveState: true })}
                                    className={`rounded px-3 py-1 text-xs border ${link.active ? 'bg-primary text-primary-foreground border-primary' : 'border-input hover:bg-accent disabled:opacity-40'}`}
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
