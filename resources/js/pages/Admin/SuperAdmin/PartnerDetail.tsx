import AdminLayout from '@/layouts/AdminLayout';
import { Button } from '@/components/ui/button';
import { router, usePage } from '@inertiajs/react';
import { PageProps } from '@/Types';

type LooseRecord = Record<string, unknown>;

function asRecord(input: unknown): LooseRecord {
    if (input && typeof input === 'object') return input as LooseRecord;
    return {};
}

// DATE FORMATTER
function formatDate(value: unknown): string {
    if (!value) return '—';
    const date = new Date(value as string);
    if (isNaN(date.getTime())) return String(value);
    return new Intl.DateTimeFormat('en-GB', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(date);
}

function formatCurrency(amount: number): string {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(amount);
}

function formatPartnerPrice(amount: unknown, currency: unknown): string {
    const value = Number(amount);
    if (!Number.isFinite(value)) return '—';
    const code = typeof currency === 'string' && currency.trim() !== '' ? currency.toUpperCase() : 'USD';
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: code }).format(value);
}

function labelize(key: string): string {
    return key
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (c) => c.toUpperCase());
}

export default function PartnerDetail({
    partner,
    stats,
    canViewPartnerPricing = false,
}: {
    partner: unknown;
    stats: unknown;
    canViewPartnerPricing?: boolean;
}) {
    const model = asRecord(partner);
    const statistics = asRecord(stats);
    const { flash, auth } = usePage<PageProps>().props;
    const canEdit = auth.role === 'super_admin' || auth.permissions.includes('partners.edit');

    const products = Array.isArray(model.products) ? model.products as LooseRecord[] : [];
    const customers = Array.isArray(model.customers) ? model.customers as LooseRecord[] : [];

    const dateFields = ['created_at', 'updated_at', 'email_verified_at', 'last_login_at', 'api_key_last_generated_at'];

    const handleGenerateApiKey = () => {
        if (confirm('This will revoke the existing API key. Continue?')) {
            router.post(route('admin.partners.generate-api-key', model.id));
        }
    };

    const handleRevokeApiKey = () => {
        if (confirm('Are you sure you want to revoke the API key?')) {
            router.delete(route('admin.partners.revoke-api-key', model.id));
        }
    };

    const toggleProductAccess = (productId: number, currentEnabled: boolean) => {
        router.post(route('admin.partners.toggle-product-access', model.id), {
            product_id: productId,
            is_enabled: !currentEnabled
        });
    };

    return (
        <AdminLayout title="Partner detail">
            {flash?.show_api_key_modal && flash?.api_key && (
                <div className="mb-6 rounded-xl border border-green-200 bg-green-50 p-4">
                    <h3 className="font-semibold text-green-800">🔑 New API Key Generated</h3>
                    <p className="text-sm text-green-700 mt-1">
                        Please copy this API key now. You won't be able to see it again!
                    </p>
                    <div className="mt-3 flex items-center gap-2">
                        <code className="flex-1 rounded bg-green-100 p-2 text-sm font-mono text-green-800">
                            {flash.api_key}
                        </code>
                        <Button
                            size="sm"
                            onClick={() => navigator.clipboard.writeText(flash.api_key as string)}
                        >
                            Copy
                        </Button>
                    </div>
                </div>
            )}

            <div className="mx-auto w-full max-w-4xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <div className="mb-6 flex items-start justify-between gap-4">
                    <div>
                        <h2 className="text-lg font-semibold text-slate-800 dark:text-white">{String(model.name ?? 'Partner')}</h2>
                        <p className="text-sm text-slate-600 dark:text-slate-300">{String(model.contact_email ?? model.email ?? 'No contact email')}</p>
                    </div>
                    <span
                        className={`rounded-full px-3 py-1 text-xs font-semibold ${
                            String(model.status ?? '').toLowerCase() === 'active'
                                ? 'bg-green-100 text-green-700'
                                : 'bg-red-100 text-red-700'
                        }`}
                    >
                        {String(model.status ?? 'unknown').toUpperCase()}
                    </span>
                </div>

                <div className="mb-6 grid grid-cols-2 md:grid-cols-4 gap-4 p-4 bg-slate-50 dark:bg-slate-800 rounded-lg">
                    <div className="text-center">
                        <div className="text-2xl font-bold text-slate-800 dark:text-white">{statistics.total_customers || 0}</div>
                        <div className="text-sm text-slate-600 dark:text-slate-300">Total Customers</div>
                    </div>
                    <div className="text-center">
                        <div className="text-2xl font-bold text-slate-800 dark:text-white">{statistics.active_customers || 0}</div>
                        <div className="text-sm text-slate-600 dark:text-slate-300">Active Customers</div>
                    </div>
                    <div className="text-center">
                        <div className="text-2xl font-bold text-slate-800 dark:text-white">{formatCurrency(Number(statistics.total_revenue) || 0)}</div>
                        <div className="text-sm text-slate-600 dark:text-slate-300">Total Revenue</div>
                    </div>
                    <div className="text-center">
                        <div className={`text-2xl font-bold ${statistics.api_key_status === 'active' ? 'text-green-600' : 'text-red-600'}`}>
                            {String(statistics.api_key_status).toUpperCase()}
                        </div>
                        <div className="text-sm text-slate-600 dark:text-slate-300">API Status</div>
                    </div>
                </div>

                {canEdit && (
                    <div className="mb-6 p-4 border border-slate-200 dark:border-slate-700 rounded-lg">
                        <h3 className="font-medium text-slate-800 dark:text-white mb-3">API Key Management</h3>
                        <p className="mb-3 text-sm text-slate-600 dark:text-slate-300">
                            Bearer API key is visible only once immediately after generate/regenerate for security.
                            If you missed it, regenerate a new key.
                        </p>
                        <div className="flex gap-2">
                            <Button onClick={handleGenerateApiKey} className="bg-blue-600 hover:bg-blue-700">
                                {statistics.api_key_status === 'active' ? 'Regenerate' : 'Generate'} API Key
                            </Button>
                            {statistics.api_key_status === 'active' && (
                                <Button variant="destructive" onClick={handleRevokeApiKey}>
                                    Revoke API Key
                                </Button>
                            )}
                        </div>
                    </div>
                )}

                <div className="space-y-3 mb-6">
                    {Object.entries(model)
                        .filter(([key]) => !['products', 'customers', 'payments', 'tokens', 'pivot'].includes(key))
                        .map(([key, value]) => (
                        <div
                            key={key}
                            className="flex items-center justify-between border-b border-slate-100 pb-2 last:border-none dark:border-slate-700"
                        >
                            <span className="text-sm font-medium text-slate-600 dark:text-slate-300">{labelize(key)}</span>
                            <span className="max-w-[60%] truncate text-sm text-slate-800 dark:text-slate-100">
                                {dateFields.includes(key)
                                    ? formatDate(value)
                                    : typeof value === 'string'
                                      ? value
                                      : typeof value === 'number'
                                        ? value
                                        : typeof value === 'boolean'
                                          ? (value ? 'Yes' : 'No')
                                      : value === null
                                        ? '—'
                                        : JSON.stringify(value)}
                            </span>
                        </div>
                    ))}
                </div>

                {products.length > 0 && (
                    <div className="mb-6">
                        <h3 className="font-medium text-slate-800 dark:text-white mb-3">Product Access</h3>
                        <div className="space-y-2">
                            {products.map((product) => {
                                const pivot = asRecord(product.pivot);
                                const isActive = Boolean(pivot.is_enabled);
                                return (
                                    <div key={String(product.id ?? product.product_code ?? Math.random())} className="flex items-center justify-between gap-3 p-3 border border-slate-200 dark:border-slate-700 rounded">
                                        <div className="min-w-0">
                                            <span className="font-medium text-slate-800 dark:text-white">{product.name}</span>
                                            <span className={`ml-2 px-2 py-1 text-xs rounded ${
                                                isActive ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600'
                                            }`}>
                                                {isActive ? 'ACTIVE' : 'INACTIVE'}
                                            </span>
                                            {canViewPartnerPricing && (
                                                <p className="mt-1 text-xs text-slate-600 dark:text-slate-300">
                                                    Price: {formatPartnerPrice(pivot.partner_price, pivot.partner_currency)}
                                                </p>
                                            )}
                                        </div>
                                        {canEdit && (
                                            <Button
                                                size="sm"
                                                variant={isActive ? 'destructive' : 'default'}
                                                onClick={() => toggleProductAccess(Number(product.id), isActive)}
                                            >
                                                {isActive ? 'Deactivate' : 'Activate'}
                                            </Button>
                                        )}
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                )}

                {customers.length > 0 && (
                    <div>
                        <h3 className="font-medium text-slate-800 dark:text-white mb-3">Recent Customers</h3>
                        <div className="space-y-2">
                            {customers.slice(0, 5).map((customer) => (
                                <div key={customer.id} className="flex items-center justify-between p-2 border-b border-slate-100 dark:border-slate-700">
                                    <div>
                                        <span className="font-medium text-slate-800 dark:text-white">
                                            {customer.first_name} {customer.last_name}
                                        </span>
                                        <span className="ml-2 text-sm text-slate-600 dark:text-slate-300">{customer.email}</span>
                                        <span className="ml-2 text-xs text-slate-500 dark:text-slate-400">
                                            Product: {String(asRecord(customer.product).name ?? '—')}
                                        </span>
                                    </div>
                                    <span className="text-sm text-slate-500">{formatDate(customer.created_at)}</span>
                                </div>
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}
