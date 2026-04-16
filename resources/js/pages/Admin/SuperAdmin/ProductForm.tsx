import { useForm, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/AdminLayout';
import { PageProps } from '@/Types';

interface ProductPayload {
    id?: number;
    name?: string;
    description?: string;
    status?: string;
    cover_duration_options?: number[];
    fields?: Record<string, string | number | boolean | null | string[]>[];
}

interface ProductFormData {
    name: string;
    description: string;
    status: string;
    cover_duration_options: number[];
    fields: Record<string, string | number | boolean | null | string[]>[];
}

export default function ProductForm({ product }: { product?: ProductPayload }) {
    const { auth } = usePage<PageProps>().props;
    const canSubmit = product
        ? auth.permissions.includes('products.edit') && ['admin', 'super_admin'].includes(auth.role ?? '')
        : auth.permissions.includes('products.create') && ['admin', 'super_admin'].includes(auth.role ?? '');
    const { data, setData, post, patch } = useForm<ProductFormData>({
        name: product?.name ?? '',
        description: product?.description ?? '',
        status: product?.status ?? 'active',
        cover_duration_options: product?.cover_duration_options ?? [12],
        fields: product?.fields ?? [],
    });

    const submit = () => {
        if (!canSubmit) return;
        if (product?.id) patch(route('admin.products.update', product.id));
        else post(route('admin.products.store'));
    };

    return (
        <AdminLayout title={product ? 'Edit Product' : 'Create Product'}>
            <Card>
                <CardHeader>
                    <CardTitle className="text-base">{product ? 'Update product details' : 'Create a new product'}</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="product-name">Name</Label>
                        <Input id="product-name" value={data.name} onChange={(e) => setData('name', e.target.value)} placeholder="Name" />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="product-description">Description</Label>
                        <textarea
                            id="product-description"
                            className="min-h-24 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                            placeholder="Description"
                        />
                    </div>
                    <Button type="button" className="bg-slate-900 hover:bg-slate-800" onClick={submit} disabled={!canSubmit}>
                        Save
                    </Button>
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
