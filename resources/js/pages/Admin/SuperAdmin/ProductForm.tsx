import { useForm, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AdminLayout from '@/layouts/AdminLayout';
import { PageProps } from '@/Types';
import { Sparkles, ImagePlus } from 'lucide-react';
import { useState } from 'react';
import React from 'react';

interface ProductPayload {
    id?: number;
    name?: string;
    description?: string;
    image?: string;
    status?: string;
}

interface ProductFormData {
    [key: string]: string | File | null;
    name: string;
    description: string;
    image: File | null;
    status: string;
}

function slugify(value: string): string {
    return value
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '_')
        .replace(/^_+|_+$/g, '');
}

export default function ProductForm({ product }: { product?: ProductPayload }) {
    const { auth } = usePage<PageProps>().props;
    const isSuperAdmin = auth.role === 'super_admin';
    const canSubmit = product
        ? isSuperAdmin || (auth.permissions.includes('products.edit') && ['admin', 'super_admin'].includes(auth.role ?? ''))
        : isSuperAdmin || (auth.permissions.includes('products.create') && ['admin', 'super_admin'].includes(auth.role ?? ''));

    const { data, setData, post, patch, errors, processing } = useForm<ProductFormData>({
        name: product?.name ?? '',
        description: product?.description ?? '',
        image: null,
        status: product?.status ?? 'active',
    });

    const [imagePreview, setImagePreview] = useState<string | null>(
        product?.image
            ? (product.image.startsWith('http') ? product.image : `/storage/${product.image}`)
            : null
    );

    const handleImageChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0] ?? null;
        setData('image', file);
        if (file) {
            const reader = new FileReader();
            reader.onload = (ev) => setImagePreview(ev.target?.result as string);
            reader.readAsDataURL(file);
        }
    };

    const submit = () => {
        if (!canSubmit) return;
        if (product?.id) {
            patch(route('admin.products.update', product.id), { preserveScroll: true });
            return;
        }
        post(route('admin.products.store'), { preserveScroll: true, forceFormData: true });
    };

    return (
        <AdminLayout title={product ? 'Edit Product' : 'Create Product'}>
            <div className="space-y-6">
                <Card className="rounded-3xl border-slate-200/80 shadow-sm">
                    <CardHeader className="space-y-2">
                        <div className="flex items-center gap-3">
                            <div className="rounded-2xl bg-emerald-50 p-2 text-emerald-700">
                                <Sparkles className="h-5 w-5" />
                            </div>
                            <div>
                                <CardTitle className="text-lg">Product identity</CardTitle>
                                <p className="text-sm text-slate-500">Define the product title, description, and publishing state.</p>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent className="grid gap-5 md:grid-cols-2">
                        <div className="space-y-2 md:col-span-2">
                            <Label htmlFor="product-name">Product name</Label>
                            <Input
                                id="product-name"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                placeholder="e.g. Premium Health Cover"
                            />
                            {errors.name && <p className="text-sm text-red-600">{errors.name}</p>}
                        </div>
                        <div className="space-y-2">
                            <Label>Current status</Label>
                            <Select value={data.status} onValueChange={(value) => setData('status', value)}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Select status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="active">Active</SelectItem>
                                    <SelectItem value="inactive">Inactive</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="space-y-2">
                            <Label>Slug preview</Label>
                            <div className="flex h-10 items-center rounded-md border border-dashed border-slate-300 bg-slate-50 px-3 text-sm text-slate-600">
                                {slugify(data.name) || 'product_slug'}
                            </div>
                        </div>
                        <div className="space-y-2 md:col-span-2">
                            <Label htmlFor="product-description">Description</Label>
                            <textarea
                                id="product-description"
                                className="min-h-32 w-full rounded-xl border border-input bg-background px-3 py-3 text-sm shadow-sm outline-none transition focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100"
                                value={data.description}
                                onChange={(e) => setData('description', e.target.value)}
                                placeholder="Explain what this product covers, who it is for, and what makes it different."
                            />
                            {errors.description && <p className="text-sm text-red-600">{errors.description}</p>}
                        </div>
                        <div className="space-y-2 md:col-span-2">
                            <Label>Product Image</Label>
                            <div className="flex items-start gap-4">
                                {imagePreview ? (
                                    <img src={imagePreview} alt="Product" className="h-28 w-28 rounded-xl border border-slate-200 object-cover" />
                                ) : (
                                    <div className="flex h-28 w-28 items-center justify-center rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 text-slate-400">
                                        <ImagePlus className="h-8 w-8" />
                                    </div>
                                )}
                                <div className="space-y-2">
                                    <input type="file" accept="image/*" id="product-image" className="hidden" onChange={handleImageChange} />
                                    <Button type="button" variant="outline" onClick={() => document.getElementById('product-image')?.click()}>
                                        {imagePreview ? 'Change Image' : 'Upload Image'}
                                    </Button>
                                    {imagePreview && (
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            className="text-red-500 hover:text-red-700"
                                            onClick={() => { setImagePreview(null); setData('image', null); }}
                                        >
                                            Remove
                                        </Button>
                                    )}
                                    <p className="text-xs text-slate-500">PNG, JPG up to 2MB</p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
                <Button type="button" className="h-12 w-full bg-slate-900 text-white hover:bg-slate-800" onClick={submit} disabled={!canSubmit || processing}>
                    {processing ? 'Saving product...' : product ? 'Update product' : 'Create product'}
                </Button>
            </div>
        </AdminLayout>
    );
}
