import AdminLayout from '@/layouts/AdminLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Package, DollarSign, Calendar, CheckCircle2, XCircle, Hash, Tag } from 'lucide-react';

interface ProductItem {
    id: number;
    name: string;
    product_code: string;
    description: string | null;
    status: string;
    category: string | null;
    base_price: number;
    guide_price: number;
    cover_duration_days_override: number | null;
    is_enabled: boolean;
}

interface PartnerInfo {
    id: number;
    name: string;
    partner_code: string;
}

interface Props {
    partner: PartnerInfo;
    products: ProductItem[];
}

export default function PartnerProducts({ partner, products }: Props) {
    return (
        <AdminLayout title="My products">
            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-gray-800">Assigned Products</h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        {partner.name} &middot; {products.length} product{products.length !== 1 ? 's' : ''}
                    </p>
                </div>

                {products.length === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-center justify-center py-12 text-sm text-muted-foreground">
                            <Package className="mb-2 h-8 w-8" />
                            <p>No products assigned yet.</p>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        {products.map((p) => (
                            <Card key={p.id} className={p.is_enabled ? '' : 'opacity-60'}>
                                <CardHeader className="pb-3">
                                    <div className="flex items-start justify-between">
                                        <div>
                                            <CardTitle className="text-base">{p.name}</CardTitle>
                                            <p className="mt-0.5 font-mono text-xs text-muted-foreground">{p.product_code}</p>
                                        </div>
                                        {p.is_enabled ? (
                                            <CheckCircle2 className="h-5 w-5 shrink-0 text-emerald-500" />
                                        ) : (
                                            <XCircle className="h-5 w-5 shrink-0 text-red-400" />
                                        )}
                                    </div>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    {p.description && (
                                        <p className="text-xs text-muted-foreground">{p.description}</p>
                                    )}

                                    <div className="space-y-2 border-t pt-3">
                                        <div className="flex items-center justify-between text-sm">
                                            <span className="flex items-center gap-1.5 text-muted-foreground">
                                                <DollarSign className="h-3.5 w-3.5" /> Base price
                                            </span>
                                            <span className="font-medium">{p.base_price.toFixed(2)}</span>
                                        </div>
                                        <div className="flex items-center justify-between text-sm">
                                            <span className="flex items-center gap-1.5 text-muted-foreground">
                                                <Tag className="h-3.5 w-3.5" /> Guide price
                                            </span>
                                            <span className="font-medium">{p.guide_price.toFixed(2)}</span>
                                        </div>
                                        {p.cover_duration_days_override && (
                                            <div className="flex items-center justify-between text-sm">
                                                <span className="flex items-center gap-1.5 text-muted-foreground">
                                                    <Calendar className="h-3.5 w-3.5" /> Duration override
                                                </span>
                                                <span className="font-medium">{p.cover_duration_days_override} days</span>
                                            </div>
                                        )}
                                        <div className="flex items-center justify-between text-sm">
                                            <span className="flex items-center gap-1.5 text-muted-foreground">
                                                <Hash className="h-3.5 w-3.5" /> Status
                                            </span>
                                            <span className={`rounded-full px-2 py-0.5 text-xs font-medium ${
                                                p.status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'
                                            }`}>
                                                {p.status}
                                            </span>
                                        </div>
                                        {p.category && (
                                            <div className="flex items-center justify-between text-sm">
                                                <span className="text-muted-foreground">Category</span>
                                                <span className="text-xs capitalize">{p.category}</span>
                                            </div>
                                        )}
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}
