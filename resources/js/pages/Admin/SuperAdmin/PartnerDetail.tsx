import AdminLayout from '@/layouts/AdminLayout';
import { Button } from '@/components/ui/button';
import { router, usePage } from '@inertiajs/react';
import { PageProps } from '@/Types';
import { useState, useEffect } from 'react';
import { Copy, Check, Link2, Link2Off } from 'lucide-react';

type LooseRecord = Record<string, unknown>;

function asRecord(input: unknown): LooseRecord {
    if (input && typeof input === 'object') return input as LooseRecord;
    return {};
}

function formatDate(value: unknown): string {
    if (!value) return '—';
    const date = new Date(value as string);
    if (isNaN(date.getTime())) return String(value);
    return new Intl.DateTimeFormat('en-GB', { dateStyle: 'medium', timeStyle: 'short' }).format(date);
}

function formatCurrency(amount: number): string {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount);
}

function formatPartnerPrice(amount: unknown, currency: unknown): string {
    const value = Number(amount);
    if (!Number.isFinite(value)) return '—';
    const code = typeof currency === 'string' && currency.trim() !== '' ? currency.toUpperCase() : 'USD';
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: code }).format(value);
}

function formatAmount(amount: unknown): string {
    const value = Number(amount);
    if (!Number.isFinite(value)) return '—';
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value);
}

function labelize(key: string): string {
    return key.replaceAll('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

function ApiKeyBox({ apiKey, onDismiss }: { apiKey: string; onDismiss: () => void }) {
    const [copied, setCopied] = useState(false);

    const handleCopy = () => {
        navigator.clipboard.writeText(apiKey).then(() => {
            setCopied(true);
            setTimeout(() => setCopied(false), 3000);
        });
    };

    return (
        <div className="mb-6 rounded-xl border-2 border-green-400 bg-green-50 p-5 dark:border-green-600 dark:bg-green-900/20">
            <div className="mb-2 flex items-center justify-between">
                <div className="flex items-center gap-2">
                    <span className="text-lg">🔑</span>
                    <h3 className="text-base font-bold text-green-800 dark:text-green-300">
                        API Key Generated — Copy it now!
                    </h3>
                </div>
                <button
                    onClick={onDismiss}
                    className="rounded px-2 py-1 text-xs font-medium text-green-700 hover:bg-green-200 dark:text-green-400"
                >
                    ✕ I've copied it, dismiss
                </button>
            </div>
            <p className="mb-4 text-sm text-green-700 dark:text-green-400">
                This key will <strong>never be shown again</strong> once you dismiss this banner.
                Copy it and use it in your partner platform connection.
            </p>
            <div className="mb-4 rounded-lg border border-green-300 bg-white p-4 dark:border-green-700 dark:bg-slate-800">
                <p className="mb-2 text-xs font-medium text-gray-500">Your API Key:</p>
                <p className="break-all select-all font-mono text-sm leading-relaxed text-slate-800 dark:text-slate-100">
                    {apiKey}
                </p>
            </div>
            <Button
                onClick={handleCopy}
                className={`flex items-center gap-2 text-white ${copied ? 'bg-green-700' : 'bg-green-600 hover:bg-green-700'}`}
            >
                {copied ? <Check className="h-4 w-4" /> : <Copy className="h-4 w-4" />}
                {copied ? 'Copied!' : 'Copy API Key'}
            </Button>
        </div>
    );
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
    const model      = asRecord(partner);
    const statistics = asRecord(stats);
    const { flash, auth } = usePage<PageProps>().props;
    const flashAny   = flash as any;
    const canEdit    = auth.role === 'super_admin' || auth.permissions.includes('partners.edit');

    const products  = Array.isArray(model.products)  ? (model.products  as LooseRecord[]) : [];
    const customers = Array.isArray(model.customers) ? (model.customers as LooseRecord[]) : [];
    const dateFields = ['created_at', 'updated_at', 'connected_at', 'last_seen_at'];
    const usefulFields = [
        'partner_code',
        'contact_email',
        'contact_phone',
        'connected_at',
        'last_seen_at',
        'status',
    ];

    const hasActiveApiKey = statistics.api_key_status === 'active';
    const isConnected = hasActiveApiKey && statistics.token_last_used_at !== 'Never';

    const [storedApiKey, setStoredApiKey] = useState<string | null>(() => {
        if (flashAny?.api_key && flashAny?.show_api_key_modal) {
            const key = String(flashAny.api_key);
            sessionStorage.setItem(`partner_api_key_${String(model.id ?? '')}`, key);
            return key;
        }
        return sessionStorage.getItem(`partner_api_key_${String(model.id ?? '')}`);
    });

    useEffect(() => {
        if (flashAny?.api_key && flashAny?.show_api_key_modal) {
            const key = String(flashAny.api_key);
            sessionStorage.setItem(`partner_api_key_${String(model.id ?? '')}`, key);
            setStoredApiKey(key);
        }
    }, [flashAny?.api_key]);

    const handleDismiss = () => {
        sessionStorage.removeItem(`partner_api_key_${String(model.id ?? '')}`);
        setStoredApiKey(null);
    };

    const handleGenerateApiKey = () => {
        if (confirm('This will revoke the existing API key. Continue?')) {
            router.post(route('admin.partners.generate-api-key', model.id));
        }
    };

    const handleRevokeApiKey = () => {
        if (confirm('Revoking the API key will disconnect this partner from all platforms. Continue?')) {
            sessionStorage.removeItem(`partner_api_key_${String(model.id ?? '')}`);
            setStoredApiKey(null);
            router.delete(route('admin.partners.revoke-api-key', model.id));
        }
    };

    const toggleProductAccess = (productId: number, currentEnabled: boolean) => {
        router.post(route('admin.partners.toggle-product-access', model.id), {
            product_id: productId,
            is_enabled: !currentEnabled,
        });
    };

    return (
        <AdminLayout title="Partner Detail">

            {/* API Key Banner */}
            {storedApiKey && <ApiKeyBox apiKey={storedApiKey} onDismiss={handleDismiss} />}

            {/* Flash messages */}
            {flashAny?.success && !flashAny?.show_api_key_modal && (
                <div className="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    ✅ {flashAny.success}
                </div>
            )}
            {flashAny?.error && (
                <div className="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    ❌ {flashAny.error}
                </div>
            )}

            <div className="mx-auto w-full max-w-6xl space-y-6">

                {/* Header */}
                <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                    <div className="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                        <div>
                            <p className="text-xs font-semibold tracking-wide text-slate-500 uppercase">Partner Profile</p>
                            <h2 className="mt-1 text-2xl font-semibold text-slate-800 dark:text-white">{String(model.name ?? 'Partner')}</h2>
                            <p className="text-sm text-slate-500">{String(model.contact_email ?? '—')}</p>
                            <p className="mt-1 font-mono text-xs text-slate-400">Code: {String(model.partner_code ?? '—')}</p>
                        </div>
                        <div className="flex flex-wrap items-center gap-2">
                            <span className={`rounded-full px-3 py-1 text-xs font-semibold ${
                                String(model.status ?? '').toLowerCase() === 'active'
                                    ? 'bg-green-100 text-green-700'
                                    : 'bg-red-100 text-red-700'
                            }`}>
                                {String(model.status ?? 'unknown').toUpperCase()}
                            </span>
                            <span className={`flex items-center gap-1 rounded-full px-3 py-1 text-xs font-semibold ${
                                isConnected
                                    ? 'bg-blue-100 text-blue-700'
                                    : 'bg-gray-100 text-gray-500'
                            }`}>
                                {isConnected
                                    ? <><Link2 className="h-3 w-3" /> API Connected</>
                                    : <><Link2Off className="h-3 w-3" /> API Not Connected</>
                                }
                            </span>
                        </div>
                    </div>
                </div>

                {/* Connection Info */}
                {isConnected && (
                    <div className="rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 dark:border-blue-800 dark:bg-blue-900/20">
                        <p className="text-sm font-medium text-blue-800 dark:text-blue-300">
                            🔗 Connected since {formatDate(model.connected_at)}
                        </p>
                        <p className="mt-1 text-xs text-blue-600 dark:text-blue-400">
                            API integration is active. This status tracks partner data-connection only (not payment collection).
                        </p>
                    </div>
                )}

                {/* Stats */}
                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                        <div className="text-2xl font-bold text-slate-800 dark:text-white">{statistics.total_customers || 0}</div>
                        <div className="text-xs text-slate-500">Total Customers</div>
                    </div>
                    <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                        <div className="text-2xl font-bold text-slate-800 dark:text-white">{statistics.active_customers || 0}</div>
                        <div className="text-xs text-slate-500">Active Customers</div>
                    </div>
                    <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                        <div className="text-2xl font-bold text-slate-800 dark:text-white">{formatCurrency(Number(statistics.total_revenue) || 0)}</div>
                        <div className="text-xs text-slate-500">Total Revenue</div>
                    </div>
                    <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                        <div className={`text-2xl font-bold ${statistics.api_key_status === 'active' ? 'text-green-600' : 'text-red-500'}`}>
                            {String(statistics.api_key_status ?? 'none').toUpperCase()}
                        </div>
                        <div className="text-xs text-slate-500">API Integration Status</div>
                    </div>
                </div>
                <div className="grid gap-4 md:grid-cols-3">
                    <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                        <div className="text-2xl font-bold text-green-600">{Number(statistics.api_success_count) || 0}</div>
                        <div className="text-xs text-slate-500">API success calls</div>
                    </div>
                    <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                        <div className="text-2xl font-bold text-red-500">{Number(statistics.api_failure_count) || 0}</div>
                        <div className="text-xs text-slate-500">API failed calls</div>
                    </div>
                    <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                        <div className="text-2xl font-bold text-slate-800 dark:text-white">{Number(statistics.api_avg_latency_ms) || 0} ms</div>
                        <div className="text-xs text-slate-500">Avg API latency</div>
                    </div>
                </div>

                {/* API Key Management */}
                {canEdit && (
                    <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                        <h3 className="mb-1 font-medium text-slate-800 dark:text-white">API Key Management</h3>
                        <p className="mb-3 text-sm text-slate-500">
                            Generate an API key for this partner. Copy it immediately — it won't be shown again.
                            The connection stays active until you revoke the key.
                        </p>
                        <div className="flex flex-wrap gap-2">
                            <Button onClick={handleGenerateApiKey} className="bg-blue-600 hover:bg-blue-700 text-white">
                                {statistics.api_key_status === 'active' ? 'Regenerate' : 'Generate'} API Key
                            </Button>
                            {statistics.api_key_status === 'active' && (
                                <Button variant="destructive" onClick={handleRevokeApiKey}>
                                    Revoke & Disconnect
                                </Button>
                            )}
                        </div>
                    </div>
                )}

                {/* Partner Fields */}
                <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                    <h3 className="mb-3 text-sm font-semibold text-slate-700 dark:text-slate-200">Partner Information</h3>
                    <div className="space-y-2">
                    {Object.entries(model)
                        .filter(([key]) => usefulFields.includes(key))
                        .filter(([key, value]) => {
                            if (dateFields.includes(key)) return Boolean(value);
                            return value !== null && value !== undefined && value !== '';
                        })
                        .map(([key, value]) => (
                            <div key={key} className="flex items-start justify-between border-b border-slate-100 pb-2 last:border-none dark:border-slate-700">
                                <span className="min-w-[160px] text-sm font-medium text-slate-500">{labelize(key)}</span>
                                <span className="max-w-[60%] break-all text-right text-sm text-slate-800 dark:text-slate-100">
                                    {dateFields.includes(key)
                                        ? formatDate(value)
                                        : typeof value === 'string'
                                            ? value || '—'
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
                </div>

                {/* Product Access */}
                {products.length > 0 && (
                    <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                        <h3 className="mb-3 font-medium text-slate-800 dark:text-white">Product Access & Pricing</h3>
                        <div className="space-y-2">
                            {products.map((product) => {
                                const pivot = asRecord(product.pivot);
                                const isActive = Boolean(pivot.is_enabled);
                                const image = typeof product.image === 'string' && product.image !== ''
                                    ? (product.image.startsWith('http') ? product.image : `/storage/${product.image}`)
                                    : null;
                                return (
                                    <div key={String(product.id ?? Math.random())} className="flex flex-col gap-3 rounded-lg border border-slate-200 p-3 md:flex-row md:items-center md:justify-between dark:border-slate-700">
                                        <div className="flex items-start gap-3">
                                            {image ? (
                                                <img src={image} alt={String(product.name ?? 'product')} className="h-14 w-14 rounded-md border border-slate-200 object-cover" />
                                            ) : (
                                                <div className="flex h-14 w-14 items-center justify-center rounded-md border border-dashed border-slate-300 text-xs text-slate-400">
                                                    No image
                                                </div>
                                            )}
                                            <div>
                                                <span className="font-medium text-slate-800 dark:text-white">{String(product.name ?? '—')}</span>
                                                <span className={`ml-2 rounded px-2 py-0.5 text-xs ${isActive ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600'}`}>
                                                    {isActive ? 'ACTIVE' : 'INACTIVE'}
                                                </span>
                                                <p className="mt-1 text-xs text-slate-500">
                                                    Base price: {formatAmount(product.base_price)}
                                                </p>
                                                <p className="text-xs text-slate-500">
                                                    Guide price: {formatAmount(product.price)}
                                                </p>

                                            </div>
                                        </div>
                                        {/* Activate/Deactivate button commented out — use Product List page to manage product status globally
                                        {canEdit && (
                                            <Button size="sm" variant={isActive ? 'destructive' : 'default'}
                                                onClick={() => toggleProductAccess(Number(product.id), isActive)}>
                                                {isActive ? 'Deactivate' : 'Activate'}
                                            </Button>
                                        )}
                                        */}
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                )}

                {/* Recent Customers */}
                {customers.length > 0 && (
                    <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                        <h3 className="mb-3 font-medium text-slate-800 dark:text-white">Recent Customers</h3>
                        <div className="space-y-2">
                            {customers.slice(0, 5).map((customer) => (
                                <div key={String(customer.id)} className="flex items-center justify-between border-b border-slate-100 p-2 last:border-none dark:border-slate-700">
                                    <div>
                                        <span className="font-medium text-slate-800 dark:text-white">
                                            {String(customer.first_name ?? '')} {String(customer.last_name ?? '')}
                                        </span>
                                        <span className="ml-2 text-sm text-slate-500">{String(customer.email ?? '—')}</span>
                                    </div>
                                    <span className="text-xs text-slate-400">{formatDate(customer.created_at)}</span>
                                </div>
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}
