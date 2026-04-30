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
    partner_id?: number | null;
    base_price?: number | string | null;
    price?: number | string | null;
}

interface PartnerOption {
    id: number;
    name: string;
}

interface ProductFormData {
    [key: string]: string | File | null | ProductFieldInput[];
    name: string;
    description: string;
    image: File | null;
    status: string;
    partner_id: string;
    base_price: string;
    price: string;
    fields: ProductFieldInput[];
}

interface ProductFieldInput {
    name: string;
    label: string;
    type: string;
    is_required: boolean;
    options?: string[];
}

function slugify(value: string): string {
    return value
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '_')
        .replace(/^_+|_+$/g, '');
}

export default function ProductForm({ product, partners = [] }: { product?: ProductPayload; partners?: PartnerOption[] }) {
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
        partner_id: product?.partner_id ? String(product.partner_id) : '',
        base_price: product?.base_price ? String(product.base_price) : '',
        price: product?.price ? String(product.price) : '',
        fields: Array.isArray((product as any)?.fields)
            ? (product as any).fields.map((field: any) => ({
                name: field.field_key ?? '',
                label: field.label ?? '',
                type: field.field_type ?? 'text',
                is_required: Boolean(field.is_required),
                options: Array.isArray(field.options) ? field.options : [],
            }))
            : [],
    });
    const addField = () => {
        const fields = Array.isArray(data.fields) ? data.fields : [];
        setData('fields', [...fields, { name: '', label: '', type: 'text', is_required: false, options: [] }]);
    };

    const updateField = (index: number, patch: Partial<ProductFieldInput>) => {
        const fields = Array.isArray(data.fields) ? data.fields : [];
        setData('fields', fields.map((field, i) => (i === index ? { ...field, ...patch } : field)));
    };

    const removeField = (index: number) => {
        const fields = Array.isArray(data.fields) ? data.fields : [];
        setData('fields', fields.filter((_, i) => i !== index));
    };


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
                            <Label>Partner <span className="text-red-500">*</span></Label>
                            <Select value={data.partner_id || ''} onValueChange={(value) => setData('partner_id', value)}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Select partner" />
                                </SelectTrigger>
                                <SelectContent>
                                    {partners.map((partner) => (
                                        <SelectItem key={partner.id} value={String(partner.id)}>
                                            {partner.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <p className="text-xs text-slate-500">Product must be mapped to a partner at creation.</p>
                            {errors.partner_id && <p className="text-sm text-red-600">{errors.partner_id}</p>}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="product-base-price">Base price</Label>
                            <Input
                                id="product-base-price"
                                type="number"
                                min="0"
                                step="0.01"
                                value={data.base_price}
                                onChange={(e) => setData('base_price', e.target.value)}
                                placeholder="0.00"
                            />
                            <p className="text-xs text-slate-500">Primary product price used for partner/user side.</p>
                            {errors.base_price && <p className="text-sm text-red-600">{errors.base_price}</p>}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="product-price">Guide price</Label>
                            <Input
                                id="product-price"
                                type="number"
                                min="0"
                                step="0.01"
                                value={data.price}
                                onChange={(e) => setData('price', e.target.value)}
                                placeholder="0.00"
                            />
                            <p className="text-xs text-slate-500">Visible only to the admin that sets it and super admin.</p>
                            {errors.price && <p className="text-sm text-red-600">{errors.price}</p>}
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
                <Card className="rounded-3xl border-slate-200/80 shadow-sm">
                    <CardHeader>
                        <CardTitle className="text-lg">Partner API field schema</CardTitle>
                        <p className="text-sm text-slate-500">These fields generate the product API payload contract shared with partners.</p>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {(Array.isArray(data.fields) ? data.fields : []).map((field, index) => (
                            <div key={index} className="grid gap-2 rounded-xl border border-slate-200 p-3 md:grid-cols-5">
                                <Input placeholder="field key" value={field.name} onChange={(e) => updateField(index, { name: e.target.value })} />
                                <Input placeholder="label" value={field.label} onChange={(e) => updateField(index, { label: e.target.value })} />
                                <Select value={field.type} onValueChange={(value) => updateField(index, { type: value })}>
                                    <SelectTrigger><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="text">Text</SelectItem>
                                        <SelectItem value="textarea">Textarea</SelectItem>
                                        <SelectItem value="number">Number</SelectItem>
                                        <SelectItem value="date">Date</SelectItem>
                                        <SelectItem value="datetime">Datetime</SelectItem>
                                        <SelectItem value="dropdown">Dropdown</SelectItem>
                                        <SelectItem value="boolean">Boolean</SelectItem>
                                        <SelectItem value="email">Email</SelectItem>
                                        <SelectItem value="phone">Phone</SelectItem>
                                    </SelectContent>
                                </Select>
                                <label className="flex items-center gap-2 text-sm">
                                    <input type="checkbox" checked={field.is_required} onChange={(e) => updateField(index, { is_required: e.target.checked })} />
                                    Required
                                </label>
                                <Button type="button" variant="outline" onClick={() => removeField(index)}>Remove</Button>
                            </div>
                        ))}
                        <Button type="button" variant="outline" onClick={addField}>Add field</Button>
                    </CardContent>
                </Card>
                <Button type="button" className="h-12 w-full bg-slate-900 text-white hover:bg-slate-800" onClick={submit} disabled={!canSubmit || processing}>
                    {processing ? 'Saving product...' : product ? 'Update product' : 'Create product'}
                </Button>
            </div>
        </AdminLayout>
    );
}
