import { useForm, usePage, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import AdminLayout from '@/layouts/AdminLayout';
import { PageProps } from '@/Types';
import { Sparkles, ImagePlus, Plus, Trash2, Code2, Users, ListTree, ExternalLink } from 'lucide-react';
import { useState } from 'react';
import React from 'react';

interface PartnerOption {
    id: number;
    name: string;
}

interface ProductFieldInput {
    name: string;
    label: string;
    type: string;
    is_required: boolean;
    options: string[];
}

interface ProductPayload {
    id?: number;
    name?: string;
    description?: string;
    image?: string;
    status?: string;
    product_code?: string;
    api_endpoint?: string | null;
    api_schema?: Record<string, unknown> | null;
    base_price?: string | number | null;
    guide_price?: string | number | null;
    cover_duration_options?: number[] | null;
    default_cover_duration_days?: number | null;
    partners?: { id: number }[];
    fields?: {
        field_key?: string;
        label?: string;
        field_type?: string;
        is_required?: boolean;
        options?: string[] | null;
    }[];
}

interface ProductFormData {
    [key: string]: string | File | null | number[] | number | string[] | ProductFieldInput[];
    name: string;
    description: string;
    image: File | null;
    status: string;
    partner_ids: number[];
    base_price: string;
    guide_price: string;
    cover_duration_options: number[];
    fields: ProductFieldInput[];
}

const FIELD_TYPES = [
    { value: 'text', label: 'Text' },
    { value: 'textarea', label: 'Long text' },
    { value: 'email', label: 'Email' },
    { value: 'phone', label: 'Phone' },
    { value: 'number', label: 'Number' },
    { value: 'date', label: 'Date' },
    { value: 'datetime', label: 'Date & time' },
    { value: 'dropdown', label: 'Dropdown' },
    { value: 'boolean', label: 'Yes / No' },
];

const KYC_FIELD_PRESETS: ProductFieldInput[] = [
    { name: 'id_type', label: 'KYC ID Type', type: 'dropdown', is_required: true, options: ['national_id', 'passport', 'drivers_license'] },
    { name: 'id_number', label: 'KYC ID Number', type: 'text', is_required: true, options: [] },
    { name: 'first_name', label: 'First Name', type: 'text', is_required: false, options: [] },
    { name: 'last_name', label: 'Last Name', type: 'text', is_required: false, options: [] },
    { name: 'dob', label: 'Date of Birth', type: 'date', is_required: false, options: [] },
    { name: 'address', label: 'Address', type: 'textarea', is_required: false, options: [] },
];

function slugify(value: string): string {
    return value
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '_')
        .replace(/^_+|_+$/g, '');
}

function parseCoverDurations(raw: string): number[] {
    return raw
        .split(',')
        .map((v) => parseInt(v.trim(), 10))
        .filter((n) => !Number.isNaN(n) && n > 0);
}

interface PartnerApiContractPayload {
    summary?: string;
    submit_endpoint?: string;
    kyc_endpoint?: string;
    required_on_every_sale?: { field: string; type: string; notes?: string }[];
    optional_on_sale?: { field: string; type: string; notes?: string }[];
    kyc_object_example?: Record<string, string>;
    headers?: Record<string, string>;
}

export default function ProductForm({
    product,
    partners = [],
    partner_api_contract,
}: {
    product?: ProductPayload;
    partners?: PartnerOption[];
    partner_api_contract?: PartnerApiContractPayload;
}) {
    const { auth } = usePage<PageProps>().props;
    const isSuperAdmin = auth.role === 'super_admin';
    const canSubmit = product
        ? isSuperAdmin || (auth.permissions.includes('products.edit') && ['admin', 'super_admin'].includes(auth.role ?? ''))
        : isSuperAdmin || (auth.permissions.includes('products.create') && ['admin', 'super_admin'].includes(auth.role ?? ''));

    const initialCoverOptions = Array.isArray(product?.cover_duration_options) && product.cover_duration_options.length > 0
        ? product.cover_duration_options
        : product?.default_cover_duration_days
            ? [product.default_cover_duration_days]
            : [30, 90, 365];

    const { data, setData, post, errors, processing } = useForm<ProductFormData>({
        name: product?.name ?? '',
        description: product?.description ?? '',
        image: null,
        status: product?.status ?? 'active',
        partner_ids: Array.isArray(product?.partners) ? product.partners.map((p) => p.id) : [],
        base_price: product?.base_price != null ? String(product.base_price) : '',
        guide_price: product?.guide_price != null ? String(product.guide_price) : '',
        cover_duration_options: initialCoverOptions,
        fields: Array.isArray(product?.fields)
            ? product.fields.map((field) => ({
                name: field.field_key ?? '',
                label: field.label ?? '',
                type: field.field_type ?? 'text',
                is_required: Boolean(field.is_required),
                options: Array.isArray(field.options) ? field.options : [],
            }))
            : [],
    });

    const [coverDurationInput, setCoverDurationInput] = useState(initialCoverOptions.join(', '));

    const addField = () => {
        const fields = Array.isArray(data.fields) ? data.fields : [];
        setData('fields', [...fields, { name: '', label: '', type: 'text', is_required: false, options: [] }]);
    };

    const addKycPresetFields = () => {
        const fields = Array.isArray(data.fields) ? data.fields : [];
        const existingKeys = new Set(fields.map((f) => f.name));
        const toAdd = KYC_FIELD_PRESETS.filter((preset) => !existingKeys.has(preset.name));
        if (toAdd.length === 0) return;
        setData('fields', [...fields, ...toAdd]);
    };

    const updateField = (index: number, patch: Partial<ProductFieldInput>) => {
        const fields = Array.isArray(data.fields) ? data.fields : [];
        setData('fields', fields.map((field, i) => (i === index ? { ...field, ...patch } : field)));
    };

    const removeField = (index: number) => {
        const fields = Array.isArray(data.fields) ? data.fields : [];
        setData('fields', fields.filter((_, i) => i !== index));
    };

    const togglePartner = (partnerId: number, checked: boolean) => {
        const current = Array.isArray(data.partner_ids) ? data.partner_ids : [];
        setData(
            'partner_ids',
            checked ? [...current, partnerId] : current.filter((id) => id !== partnerId),
        );
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

    const handleCoverDurationBlur = () => {
        const parsed = parseCoverDurations(coverDurationInput);
        setData('cover_duration_options', parsed.length > 0 ? parsed : [365]);
    };

    const productCode = product?.product_code ?? null;
    const apiEndpoint = product?.api_endpoint ?? (productCode ? `/api/v1/products/${productCode}` : null);

    const submit = () => {
        if (!canSubmit) return;
        handleCoverDurationBlur();
        if (product?.id) {
            post(route('admin.products.update', product.id), {
                forceFormData: true,
                preserveScroll: true,
                data: { ...data, _method: 'PATCH' } as any,
            });
            return;
        }
        post(route('admin.products.store'), { preserveScroll: true, forceFormData: true });
    };

    return (
        <AdminLayout title={product ? 'Edit Product' : 'Create Product'}>
            <div className="space-y-6">
                {partner_api_contract && (
                    <Card className="rounded-3xl border-amber-200/80 bg-amber-50/40 shadow-sm">
                        <CardHeader className="space-y-2">
                            <CardTitle className="text-lg text-amber-950">What the partner must send when they sell this product</CardTitle>
                            <p className="text-sm text-amber-900/80">{partner_api_contract.summary}</p>
                        </CardHeader>
                        <CardContent className="grid gap-6 lg:grid-cols-2">
                            <div>
                                <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-amber-800">Required on every sale (POST submit)</p>
                                <ul className="space-y-1.5 text-sm text-amber-950">
                                    {(partner_api_contract.required_on_every_sale ?? []).map((row) => (
                                        <li key={row.field}>
                                            <code className="rounded bg-white/80 px-1 text-xs">{row.field}</code>
                                            <span className="text-amber-800"> — {row.type}</span>
                                            {row.notes && <span className="block text-xs text-amber-700">{row.notes}</span>}
                                        </li>
                                    ))}
                                </ul>
                                <p className="mt-4 mb-2 text-xs font-semibold uppercase tracking-wide text-amber-800">Plus your custom fields below</p>
                                <p className="text-sm text-amber-900/80">
                                    Define fields in <strong>Dynamic fields</strong>. Partner fetches schema via{' '}
                                    <code className="rounded bg-white/80 px-1 text-xs">GET /api/v1/products/&#123;code&#125;/fields</code> and returns values in submit body or KYC object.
                                </p>
                            </div>
                            <div>
                                <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-amber-800">KYC object (submit or separate KYC call)</p>
                                <pre className="overflow-x-auto rounded-xl border border-amber-200 bg-white/80 p-3 text-xs text-slate-800">
{JSON.stringify(partner_api_contract.kyc_object_example ?? {}, null, 2)}
                                </pre>
                                <p className="mt-3 font-mono text-xs text-amber-900">{partner_api_contract.submit_endpoint}</p>
                                <p className="font-mono text-xs text-amber-900">{partner_api_contract.kyc_endpoint}</p>
                            </div>
                        </CardContent>
                    </Card>
                )}

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
                            <Label>{productCode ? 'Product code' : 'Slug preview'}</Label>
                            <div className="flex h-10 items-center rounded-md border border-dashed border-slate-300 bg-slate-50 px-3 font-mono text-sm text-slate-600">
                                {productCode ?? (slugify(data.name) || 'product_slug')}
                            </div>
                            {productCode && (
                                <p className="text-xs text-slate-500">Partners use this code in all API URLs.</p>
                            )}
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
                            <div className="flex flex-col items-start gap-4 sm:flex-row">
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
                        <div className="space-y-2">
                            <Label htmlFor="base-price">Base price (optional)</Label>
                            <Input
                                id="base-price"
                                type="number"
                                min="0"
                                step="0.01"
                                value={data.base_price}
                                onChange={(e) => setData('base_price', e.target.value)}
                                placeholder="0.00"
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="guide-price">Guide price (optional)</Label>
                            <Input
                                id="guide-price"
                                type="number"
                                min="0"
                                step="0.01"
                                value={data.guide_price}
                                onChange={(e) => setData('guide_price', e.target.value)}
                                placeholder="0.00"
                            />
                        </div>
                        <div className="space-y-2 md:col-span-2">
                            <Label htmlFor="cover-durations">Cover duration options (days, comma-separated)</Label>
                            <Input
                                id="cover-durations"
                                value={coverDurationInput}
                                onChange={(e) => setCoverDurationInput(e.target.value)}
                                onBlur={handleCoverDurationBlur}
                                placeholder="30, 90, 365"
                            />
                            <p className="text-xs text-slate-500">Partners send one of these values as cover_duration when submitting a policy.</p>
                        </div>
                    </CardContent>
                </Card>

                <Card className="rounded-3xl border-slate-200/80 shadow-sm">
                    <CardHeader className="space-y-2">
                        <div className="flex items-center gap-3">
                            <div className="rounded-2xl bg-blue-50 p-2 text-blue-700">
                                <Users className="h-5 w-5" />
                            </div>
                            <div>
                                <CardTitle className="text-lg">Partner access</CardTitle>
                                <p className="text-sm text-slate-500">
                                    Select which partners can see this product in <code className="rounded bg-slate-100 px-1 text-xs">GET /api/v1/partner/products</code> and submit policies.
                                </p>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        {partners.length === 0 ? (
                            <p className="text-sm text-slate-500">No active partners found. Create a partner first, then assign this product.</p>
                        ) : (
                            <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                {partners.map((partner) => {
                                    const checked = (data.partner_ids ?? []).includes(partner.id);
                                    return (
                                        <label
                                            key={partner.id}
                                            className={`flex cursor-pointer items-center gap-3 rounded-xl border px-4 py-3 transition ${checked ? 'border-emerald-300 bg-emerald-50/60' : 'border-slate-200 hover:border-slate-300'}`}
                                        >
                                            <Checkbox
                                                checked={checked}
                                                onCheckedChange={(value) => togglePartner(partner.id, value === true)}
                                            />
                                            <span className="text-sm font-medium text-slate-800">{partner.name}</span>
                                        </label>
                                    );
                                })}
                            </div>
                        )}
                        {errors.partner_ids && <p className="mt-2 text-sm text-red-600">{errors.partner_ids}</p>}
                    </CardContent>
                </Card>

                <Card className="rounded-3xl border-slate-200/80 shadow-sm">
                    <CardHeader className="space-y-2">
                        <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div className="flex items-center gap-3">
                                <div className="rounded-2xl bg-violet-50 p-2 text-violet-700">
                                    <ListTree className="h-5 w-5" />
                                </div>
                                <div>
                                    <CardTitle className="text-lg">Dynamic fields (KYC &amp; policy data)</CardTitle>
                                    <p className="text-sm text-slate-500">
                                        Define extra fields partners must collect. Saving rebuilds the product <code className="rounded bg-slate-100 px-1 text-xs">api_schema</code> exposed via the partner API.
                                    </p>
                                </div>
                            </div>
                            <div className="flex flex-wrap gap-2">
                                <Button type="button" variant="outline" size="sm" onClick={addKycPresetFields}>
                                    Add KYC preset
                                </Button>
                                <Button type="button" variant="outline" size="sm" onClick={addField} className="gap-1">
                                    <Plus className="h-4 w-4" /> Add field
                                </Button>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {(data.fields ?? []).length === 0 ? (
                            <div className="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                                No custom fields yet. Add fields for KYC (ID type, ID number) or policy-specific questions. Partners can also send a <code className="rounded bg-white px-1 text-xs">kyc</code> object on submit or via the dedicated KYC endpoint.
                            </div>
                        ) : (
                            (data.fields ?? []).map((field, index) => (
                                <div key={index} className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                                    <div className="mb-3 flex items-center justify-between">
                                        <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Field {index + 1}</p>
                                        <Button type="button" variant="ghost" size="sm" className="text-red-500 hover:text-red-700" onClick={() => removeField(index)}>
                                            <Trash2 className="h-4 w-4" />
                                        </Button>
                                    </div>
                                    <div className="grid gap-4 md:grid-cols-2">
                                        <div className="space-y-2">
                                            <Label>Field key</Label>
                                            <Input
                                                value={field.name}
                                                onChange={(e) => updateField(index, { name: e.target.value })}
                                                placeholder="e.g. id_number"
                                            />
                                            <p className="text-xs text-slate-400">API key: {slugify(field.name) || '—'}</p>
                                        </div>
                                        <div className="space-y-2">
                                            <Label>Label</Label>
                                            <Input
                                                value={field.label}
                                                onChange={(e) => updateField(index, { label: e.target.value })}
                                                placeholder="e.g. KYC ID Number"
                                            />
                                        </div>
                                        <div className="space-y-2">
                                            <Label>Type</Label>
                                            <Select value={field.type} onValueChange={(value) => updateField(index, { type: value })}>
                                                <SelectTrigger>
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {FIELD_TYPES.map((t) => (
                                                        <SelectItem key={t.value} value={t.value}>{t.label}</SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        <div className="flex items-end gap-2 pb-1">
                                            <Checkbox
                                                id={`required-${index}`}
                                                checked={field.is_required}
                                                onCheckedChange={(value) => updateField(index, { is_required: value === true })}
                                            />
                                            <Label htmlFor={`required-${index}`} className="cursor-pointer">Required</Label>
                                        </div>
                                        {field.type === 'dropdown' && (
                                            <div className="space-y-2 md:col-span-2">
                                                <Label>Options (comma-separated)</Label>
                                                <Input
                                                    value={(field.options ?? []).join(', ')}
                                                    onChange={(e) => updateField(index, {
                                                        options: e.target.value.split(',').map((o) => o.trim()).filter(Boolean),
                                                    })}
                                                    placeholder="national_id, passport, drivers_license"
                                                />
                                            </div>
                                        )}
                                    </div>
                                </div>
                            ))
                        )}
                    </CardContent>
                </Card>

                <Card className="rounded-3xl border-slate-200/80 shadow-sm">
                    <CardHeader className="space-y-2">
                        <div className="flex items-center gap-3">
                            <div className="rounded-2xl bg-slate-100 p-2 text-slate-700">
                                <Code2 className="h-5 w-5" />
                            </div>
                            <div>
                                <CardTitle className="text-lg">Partner API flow</CardTitle>
                                <p className="text-sm text-slate-500">How partners fetch this product and send KYC / policy data.</p>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent className="space-y-4 text-sm text-slate-700">
                        <ol className="list-decimal space-y-2 pl-5 leading-relaxed">
                            <li>
                                Partner calls <code className="rounded bg-slate-100 px-1 text-xs">GET /api/v1/partner/products</code> (Bearer token) to list assigned active products and read <code className="rounded bg-slate-100 px-1 text-xs">product_code</code>.
                            </li>
                            <li>
                                Partner fetches field schema: <code className="rounded bg-slate-100 px-1 text-xs">GET /api/v1/partner/products/&#123;uuid&#125;/schema</code> or <code className="rounded bg-slate-100 px-1 text-xs">GET /api/v1/products/&#123;product_code&#125;/fields</code>.
                            </li>
                            <li>
                                Partner submits a sale: <code className="rounded bg-slate-100 px-1 text-xs">POST /api/v1/products/&#123;product_code&#125;/submit</code> with <code className="rounded bg-slate-100 px-1 text-xs">Idempotency-Key</code> header (optionally include <code className="rounded bg-slate-100 px-1 text-xs">kyc</code> in the body).
                            </li>
                            <li>
                                Partner sends KYC separately: <code className="rounded bg-slate-100 px-1 text-xs">POST /api/v1/products/&#123;product_code&#125;/transactions/&#123;txn&#125;/kyc</code> with a <code className="rounded bg-slate-100 px-1 text-xs">kyc</code> object.
                            </li>
                        </ol>
                        {apiEndpoint && (
                            <div className="rounded-xl border border-emerald-200 bg-emerald-50/50 px-4 py-3">
                                <p className="text-xs font-semibold uppercase tracking-wide text-emerald-800">This product&apos;s endpoints</p>
                                <ul className="mt-2 space-y-1 font-mono text-xs text-emerald-900">
                                    <li>POST {apiEndpoint}/submit</li>
                                    <li>POST {apiEndpoint}/transactions/&#123;transaction_number&#125;/kyc</li>
                                    <li>GET /api/v1/products/{productCode}/fields</li>
                                </ul>
                            </div>
                        )}
                        <Link
                            href={route('partner.api-documentation')}
                            className="inline-flex items-center gap-1.5 text-sm font-medium text-emerald-700 hover:text-emerald-900"
                        >
                            Full partner API documentation <ExternalLink className="h-4 w-4" />
                        </Link>
                    </CardContent>
                </Card>

                <Button type="button" className="h-12 w-full bg-slate-900 text-white hover:bg-slate-800" onClick={submit} disabled={!canSubmit || processing}>
                    {processing ? 'Saving product...' : product ? 'Update product' : 'Create product'}
                </Button>
            </div>
        </AdminLayout>
    );
}
