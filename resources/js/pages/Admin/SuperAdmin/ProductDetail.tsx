import AdminLayout from '@/layouts/AdminLayout';
import { Link, router } from '@inertiajs/react';
import { Pencil, Users, ArrowLeft, ToggleLeft, ToggleRight, CheckCircle2, XCircle } from 'lucide-react';
import { Button } from '@/components/ui/button';

interface Currency {
    code: string;
    symbol: string | null;
    name: string;
}

interface PartnerPricing {
    id: number;
    name: string;
    partner_code: string;
    email: string | null;
    status: string;
    is_enabled: boolean;
    currency: Currency | null;
    base_price: string | null;
    guide_price: string | null;
}

interface Product {
    id: number;
    name: string;
    product_name: string | null;
    product_code: string;
    description: string | null;
    status: string;
    image: string | null;
    category: string | null;
    default_cover_duration_days: number | null;
    created_at: string;
}

interface Props {
    product: Product;
    partnerPricing: PartnerPricing[];
}

export default function ProductDetail({ product, partnerPricing }: Props) {
    const imageUrl = product.image
        ? (product.image.startsWith('http') ? product.image : `/storage/${product.image}`)
        : null;

    const toggleStatus = () => {
        router.post(route('admin.products.toggle-status', product.id), {}, { preserveScroll: true });
    };

    return (
        <AdminLayout title="Product Detail">
            <div className="space-y-6">

                {/* Back + Actions */}
                <div className="flex items-center justify-between">
                    <Link
                        href={route('admin.products.index')}
                        className="flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-800"
                    >
                        <ArrowLeft className="h-4 w-4" /> Back to Products
                    </Link>
                    <div className="flex items-center gap-2">
                        <Link href={route('admin.products.assign-partners', product.id)}>
                            <Button variant="outline" size="sm" className="gap-1.5">
                                <Users className="h-4 w-4" /> Manage Partners
                            </Button>
                        </Link>
                        <Link href={route('admin.products.edit', product.id)}>
                            <Button variant="outline" size="sm" className="gap-1.5">
                                <Pencil className="h-4 w-4" /> Edit Product
                            </Button>
                        </Link>
                        <Button
                            size="sm"
                            variant="outline"
                            onClick={toggleStatus}
                            className={product.status === 'active'
                                ? 'gap-1.5 border-emerald-200 text-emerald-700 hover:bg-emerald-50'
                                : 'gap-1.5 border-slate-200 text-slate-500 hover:bg-slate-50'}
                        >
                            {product.status === 'active'
                                ? <><ToggleRight className="h-4 w-4" /> Active</>
                                : <><ToggleLeft className="h-4 w-4" /> Inactive</>}
                        </Button>
                    </div>
                </div>

                {/* Product Info Card */}
                <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div className="flex flex-col gap-6 p-6 sm:flex-row">
                        {/* Image */}
                        <div className="shrink-0">
                            {imageUrl ? (
                                <img
                                    src={imageUrl}
                                    alt={product.name}
                                    className="h-28 w-28 rounded-xl border border-slate-200 object-cover"
                                />
                            ) : (
                                <div className="flex h-28 w-28 items-center justify-center rounded-xl border-2 border-dashed border-slate-200 bg-slate-50 text-slate-400 text-xs">
                                    No image
                                </div>
                            )}
                        </div>

                        {/* Info */}
                        <div className="flex-1 space-y-3">
                            <div className="flex flex-wrap items-start justify-between gap-2">
                                <div>
                                    <h1 className="text-xl font-semibold text-slate-900">
                                        {product.product_name || product.name}
                                    </h1>
                                    <p className="mt-0.5 font-mono text-xs text-slate-400">{product.product_code}</p>
                                </div>
                                <span className={`rounded-full px-3 py-1 text-xs font-semibold ${
                                    product.status === 'active'
                                        ? 'bg-emerald-100 text-emerald-700'
                                        : 'bg-slate-100 text-slate-500'
                                }`}>
                                    {product.status}
                                </span>
                            </div>

                            {product.description && (
                                <p className="text-sm text-slate-600 leading-relaxed">{product.description}</p>
                            )}

                            <div className="flex flex-wrap gap-4 pt-1 text-sm text-slate-500">
                                {product.category && (
                                    <span><span className="font-medium text-slate-700">Category:</span> {product.category}</span>
                                )}
                                {product.default_cover_duration_days && (
                                    <span><span className="font-medium text-slate-700">Cover Duration:</span> {product.default_cover_duration_days} days</span>
                                )}
                                <span><span className="font-medium text-slate-700">Partners:</span> {partnerPricing.length}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Partner Pricing Table */}
                <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div className="border-b border-slate-100 px-6 py-4">
                        <h2 className="text-base font-semibold text-slate-800">Partner Pricing</h2>
                        <p className="mt-0.5 text-sm text-slate-500">
                            Per-partner selling price and currency for this product.
                        </p>
                    </div>

                    {partnerPricing.length === 0 ? (
                        <div className="px-6 py-12 text-center">
                            <Users className="mx-auto h-10 w-10 text-slate-300" />
                            <p className="mt-3 text-sm text-slate-500">No partners assigned yet.</p>
                            <Link href={route('admin.products.assign-partners', product.id)} className="mt-3 inline-block">
                                <Button size="sm" className="bg-emerald-600 text-white hover:bg-emerald-700">
                                    Assign Partners
                                </Button>
                            </Link>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-slate-100 bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        <th className="px-6 py-3">Partner</th>
                                        <th className="px-6 py-3">Partner Code</th>
                                        <th className="px-6 py-3">Currency</th>
                                        <th className="px-6 py-3">Base Price</th>
                                        <th className="px-6 py-3">Guide Price</th>
                                        <th className="px-6 py-3">Access</th>
                                        <th className="px-6 py-3">Status</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-50">
                                    {partnerPricing.map((p) => (
                                        <tr key={p.id} className="hover:bg-slate-50/60 transition-colors">
                                            <td className="px-6 py-4">
                                                <div className="font-medium text-slate-800">{p.name}</div>
                                                {p.email && (
                                                    <div className="text-xs text-slate-400">{p.email}</div>
                                                )}
                                            </td>
                                            <td className="px-6 py-4 font-mono text-xs text-slate-500">
                                                {p.partner_code}
                                            </td>
                                            <td className="px-6 py-4">
                                                {p.currency ? (
                                                    <div className="flex items-center gap-1.5">
                                                        <span className="rounded bg-blue-50 px-2 py-0.5 font-mono text-xs font-semibold text-blue-700">
                                                            {p.currency.code}
                                                        </span>
                                                        {p.currency.symbol && (
                                                            <span className="text-slate-400">{p.currency.symbol}</span>
                                                        )}
                                                    </div>
                                                ) : (
                                                    <span className="text-slate-300">—</span>
                                                )}
                                            </td>
                                            <td className="px-6 py-4">
                                                {p.base_price ? (
                                                    <span className="font-semibold text-slate-800">
                                                        {p.currency?.symbol ?? ''}{Number(p.base_price).toLocaleString(undefined, { minimumFractionDigits: 2 })}
                                                    </span>
                                                ) : (
                                                    <span className="text-slate-300">—</span>
                                                )}
                                            </td>
                                            <td className="px-6 py-4">
                                                {p.guide_price ? (
                                                    <span className="font-semibold text-emerald-700">
                                                        {p.currency?.symbol ?? ''}{Number(p.guide_price).toLocaleString(undefined, { minimumFractionDigits: 2 })}
                                                    </span>
                                                ) : (
                                                    <span className="text-slate-300">—</span>
                                                )}
                                            </td>
                                            <td className="px-6 py-4">
                                                {p.is_enabled ? (
                                                    <span className="flex items-center gap-1 text-emerald-600">
                                                        <CheckCircle2 className="h-4 w-4" /> Enabled
                                                    </span>
                                                ) : (
                                                    <span className="flex items-center gap-1 text-slate-400">
                                                        <XCircle className="h-4 w-4" /> Disabled
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-6 py-4">
                                                <span className={`rounded-full px-2.5 py-0.5 text-xs font-medium ${
                                                    p.status === 'active'
                                                        ? 'bg-emerald-100 text-emerald-700'
                                                        : 'bg-slate-100 text-slate-500'
                                                }`}>
                                                    {p.status}
                                                </span>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>

            </div>
        </AdminLayout>
    );
}
