import { useForm, usePage } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import AdminLayout from '@/layouts/AdminLayout';
import { PageProps } from '@/Types';
import { cn } from '@/lib/utils';
import { CheckCircle2, CirclePlus, Layers3, Package2, ShieldCheck, Sparkles, Trash2 } from 'lucide-react';

interface ProductPayload {
    id?: number;
    name?: string;
    description?: string;
    status?: string;
    cover_duration_options?: number[];
    fields?: ProductFieldForm[];
}

type ProductFieldType = 'text' | 'textarea' | 'number' | 'date' | 'datetime' | 'dropdown' | 'boolean' | 'email' | 'phone';

interface ProductFieldForm {
    [key: string]: string | number | boolean | string[] | undefined;
    id?: number;
    name: string;
    label: string;
    type: ProductFieldType;
    is_required: boolean;
    options: string[];
}

interface ProductFormData {
    [key: string]: string | number | string[] | number[] | ProductFieldForm[];
    name: string;
    description: string;
    status: string;
    cover_duration_options: number[];
    fields: ProductFieldForm[];
}

const durationSuggestions = [1, 3, 6, 12, 24, 36];
const fieldTypeOptions: Array<{ value: ProductFieldType; label: string; hint: string }> = [
    { value: 'text', label: 'Text', hint: 'Single-line input' },
    { value: 'textarea', label: 'Textarea', hint: 'Longer freeform answer' },
    { value: 'number', label: 'Number', hint: 'Numeric-only field' },
    { value: 'date', label: 'Date', hint: 'Date picker style input' },
    { value: 'datetime', label: 'Date & time', hint: 'Timestamp value' },
    { value: 'dropdown', label: 'Dropdown', hint: 'Controlled list of options' },
    { value: 'boolean', label: 'Yes / No', hint: 'Checkbox-like field' },
    { value: 'email', label: 'Email', hint: 'Email validation' },
    { value: 'phone', label: 'Phone', hint: 'Phone validation' },
];

function createEmptyField(index: number): ProductFieldForm {
    return {
        name: `field_${index + 1}`,
        label: '',
        type: 'text',
        is_required: false,
        options: [],
    };
}

function normalizeFields(fields?: ProductPayload['fields']): ProductFieldForm[] {
    if (!Array.isArray(fields) || fields.length === 0) {
        return [createEmptyField(0)];
    }

    return fields.map((field, index) => ({
        id: field.id,
        name: field.name || `field_${index + 1}`,
        label: field.label || '',
        type: field.type || 'text',
        is_required: Boolean(field.is_required),
        options: Array.isArray(field.options) ? field.options.filter(Boolean) : [],
    }));
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
    const canSubmit = product
        ? auth.permissions.includes('products.edit') && ['admin', 'super_admin'].includes(auth.role ?? '')
        : auth.permissions.includes('products.create') && ['admin', 'super_admin'].includes(auth.role ?? '');
    const { data, setData, post, patch, errors, processing } = useForm<ProductFormData>({
        name: product?.name ?? '',
        description: product?.description ?? '',
        status: product?.status ?? 'active',
        cover_duration_options: product?.cover_duration_options ?? [12],
        fields: normalizeFields(product?.fields),
    });

    const submit = () => {
        if (!canSubmit) return;
        if (product?.id) {
            patch(route('admin.products.update', product.id), { preserveScroll: true });
            return;
        }

        post(route('admin.products.store'), { preserveScroll: true });
    };

    const updateField = <K extends keyof ProductFieldForm>(index: number, key: K, value: ProductFieldForm[K]) => {
        setData(
            'fields',
            data.fields.map((field, fieldIndex) => {
                if (fieldIndex !== index) return field;

                const nextField = { ...field, [key]: value };
                if (key === 'label' && !field.name) {
                    nextField.name = slugify(String(value)) || `field_${index + 1}`;
                }

                if (key === 'type' && value !== 'dropdown') {
                    nextField.options = [];
                }

                return nextField;
            }),
        );
    };

    const addField = () => {
        setData('fields', [...data.fields, createEmptyField(data.fields.length)]);
    };

    const removeField = (index: number) => {
        setData(
            'fields',
            data.fields.length === 1 ? [createEmptyField(0)] : data.fields.filter((_, fieldIndex) => fieldIndex !== index),
        );
    };

    const addDuration = (months: number) => {
        if (data.cover_duration_options.includes(months)) return;
        setData('cover_duration_options', [...data.cover_duration_options, months].sort((a, b) => a - b));
    };

    const removeDuration = (months: number) => {
        const next = data.cover_duration_options.filter((value) => value !== months);
        setData('cover_duration_options', next.length > 0 ? next : [12]);
    };

    const updateDropdownOptions = (index: number, rawValue: string) => {
        updateField(
            index,
            'options',
            rawValue
                .split(',')
                .map((option) => option.trim())
                .filter(Boolean),
        );
    };

    const activeFieldCount = data.fields.filter((field) => field.label.trim() || field.name.trim()).length;
    const requiredFieldCount = data.fields.filter((field) => field.is_required).length;

    return (
        <AdminLayout title={product ? 'Edit Product' : 'Create Product'}>
            <div className="space-y-6">
                <Card className="overflow-hidden border-0 bg-gradient-to-r from-slate-950 via-slate-900 to-emerald-950 text-white shadow-[0_20px_60px_rgba(15,23,42,0.28)]">
                    <CardContent className="grid gap-6 p-6 lg:grid-cols-[minmax(0,1.35fr)_340px] lg:p-8">
                        <div className="space-y-5">
                            <div className="flex flex-wrap items-center gap-3">
                                <Badge className="border-white/20 bg-white/10 text-white" variant="outline">
                                    {product ? 'Product editor' : 'New product setup'}
                                </Badge>
                                <Badge className={cn('border-0', data.status === 'active' ? 'bg-emerald-500/20 text-emerald-100' : 'bg-amber-500/20 text-amber-100')}>
                                    {data.status === 'active' ? 'Active product' : 'Inactive draft'}
                                </Badge>
                            </div>
                            <div className="space-y-3">
                                <h2 className="text-2xl font-semibold tracking-tight lg:text-3xl">{data.name.trim() || 'Build a premium product experience'}</h2>
                                <p className="max-w-2xl text-sm leading-6 text-slate-200/85">
                                    Configure product identity, claim duration choices, and the exact form fields partners must submit. This editor is designed to feel closer to a polished SaaS operations tool instead of a basic form.
                                </p>
                            </div>
                            <div className="grid gap-3 sm:grid-cols-3">
                                <div className="rounded-2xl border border-white/10 bg-white/5 p-4">
                                    <p className="text-xs uppercase tracking-[0.24em] text-slate-300">Coverage plans</p>
                                    <p className="mt-2 text-2xl font-semibold">{data.cover_duration_options.length}</p>
                                    <p className="mt-1 text-xs text-slate-300">Duration options configured</p>
                                </div>
                                <div className="rounded-2xl border border-white/10 bg-white/5 p-4">
                                    <p className="text-xs uppercase tracking-[0.24em] text-slate-300">Input fields</p>
                                    <p className="mt-2 text-2xl font-semibold">{activeFieldCount}</p>
                                    <p className="mt-1 text-xs text-slate-300">Customer-facing fields</p>
                                </div>
                                <div className="rounded-2xl border border-white/10 bg-white/5 p-4">
                                    <p className="text-xs uppercase tracking-[0.24em] text-slate-300">Required</p>
                                    <p className="mt-2 text-2xl font-semibold">{requiredFieldCount}</p>
                                    <p className="mt-1 text-xs text-slate-300">Mandatory data points</p>
                                </div>
                            </div>
                        </div>

                        <div className="rounded-3xl border border-white/10 bg-white/8 p-5 backdrop-blur">
                            <div className="flex items-start justify-between gap-4">
                                <div>
                                    <p className="text-sm font-medium text-slate-100">Product snapshot</p>
                                    <p className="mt-1 text-xs text-slate-300">A quick summary of what will be saved.</p>
                                </div>
                                <Package2 className="h-5 w-5 text-emerald-300" />
                            </div>
                            <Separator className="my-4 bg-white/10" />
                            <div className="space-y-4 text-sm">
                                <div className="rounded-2xl bg-black/20 p-4">
                                    <p className="text-xs uppercase tracking-wide text-slate-400">Product name</p>
                                    <p className="mt-1 font-medium text-white">{data.name.trim() || 'Untitled product'}</p>
                                </div>
                                <div className="rounded-2xl bg-black/20 p-4">
                                    <p className="text-xs uppercase tracking-wide text-slate-400">Slug preview</p>
                                    <p className="mt-1 font-medium text-emerald-200">{slugify(data.name) || 'product_slug'}</p>
                                </div>
                                <div className="rounded-2xl bg-black/20 p-4">
                                    <p className="text-xs uppercase tracking-wide text-slate-400">Durations</p>
                                    <div className="mt-2 flex flex-wrap gap-2">
                                        {data.cover_duration_options.map((months) => (
                                            <Badge key={months} className="border-emerald-300/30 bg-emerald-400/10 text-emerald-100" variant="outline">
                                                {months} months
                                            </Badge>
                                        ))}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
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
                            </CardContent>
                        </Card>

                        <Card className="rounded-3xl border-slate-200/80 shadow-sm">
                            <CardHeader className="space-y-2">
                                <div className="flex items-center gap-3">
                                    <div className="rounded-2xl bg-blue-50 p-2 text-blue-700">
                                        <ShieldCheck className="h-5 w-5" />
                                    </div>
                                    <div>
                                        <CardTitle className="text-lg">Coverage duration setup</CardTitle>
                                        <p className="text-sm text-slate-500">Choose the duration options partners can submit against this product.</p>
                                    </div>
                                </div>
                            </CardHeader>
                            <CardContent className="space-y-5">
                                <div className="flex flex-wrap gap-2">
                                    {durationSuggestions.map((months) => {
                                        const active = data.cover_duration_options.includes(months);
                                        return (
                                            <button
                                                key={months}
                                                type="button"
                                                onClick={() => (active ? removeDuration(months) : addDuration(months))}
                                                className={cn(
                                                    'inline-flex items-center gap-2 rounded-full border px-4 py-2 text-sm font-medium transition',
                                                    active
                                                        ? 'border-emerald-500 bg-emerald-50 text-emerald-700'
                                                        : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50',
                                                )}
                                            >
                                                {active && <CheckCircle2 className="h-4 w-4" />}
                                                {months} months
                                            </button>
                                        );
                                    })}
                                </div>
                                <div className="rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-4">
                                    <p className="text-sm font-medium text-slate-700">Selected durations</p>
                                    <div className="mt-3 flex flex-wrap gap-2">
                                        {data.cover_duration_options.map((months) => (
                                            <button
                                                key={months}
                                                type="button"
                                                onClick={() => removeDuration(months)}
                                                className="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 transition hover:border-red-200 hover:text-red-600"
                                            >
                                                {months} months x
                                            </button>
                                        ))}
                                    </div>
                                    {errors.cover_duration_options && <p className="mt-3 text-sm text-red-600">{errors.cover_duration_options}</p>}
                                </div>
                            </CardContent>
                        </Card>

                        <Card className="rounded-3xl border-slate-200/80 shadow-sm">
                            <CardHeader className="space-y-2">
                                <div className="flex items-center justify-between gap-4">
                                    <div className="flex items-center gap-3">
                                        <div className="rounded-2xl bg-violet-50 p-2 text-violet-700">
                                            <Layers3 className="h-5 w-5" />
                                        </div>
                                        <div>
                                            <CardTitle className="text-lg">Customer submission fields</CardTitle>
                                            <p className="text-sm text-slate-500">Build the fields partners must submit for this product.</p>
                                        </div>
                                    </div>
                                    <Button type="button" variant="outline" onClick={addField}>
                                        <CirclePlus className="mr-2 h-4 w-4" />
                                        Add field
                                    </Button>
                                </div>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {data.fields.map((field, index) => (
                                    <div key={`${field.id ?? 'new'}-${index}`} className="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                                        <div className="mb-4 flex items-center justify-between gap-3">
                                            <div>
                                                <p className="text-sm font-semibold text-slate-900">Field {index + 1}</p>
                                                <p className="text-xs text-slate-500">Configure label, data key, type, and requirement.</p>
                                            </div>
                                            <Button type="button" variant="ghost" className="text-red-600 hover:bg-red-50 hover:text-red-700" onClick={() => removeField(index)}>
                                                <Trash2 className="mr-2 h-4 w-4" />
                                                Remove
                                            </Button>
                                        </div>
                                        <div className="grid gap-4 md:grid-cols-2">
                                            <div className="space-y-2">
                                                <Label>Field label</Label>
                                                <Input
                                                    value={field.label}
                                                    onChange={(e) => updateField(index, 'label', e.target.value)}
                                                    placeholder="e.g. National ID Number"
                                                />
                                            </div>
                                            <div className="space-y-2">
                                                <Label>Field key</Label>
                                                <Input
                                                    value={field.name}
                                                    onChange={(e) => updateField(index, 'name', slugify(e.target.value))}
                                                    placeholder="e.g. national_id_number"
                                                />
                                            </div>
                                            <div className="space-y-2">
                                                <Label>Field type</Label>
                                                <Select value={field.type} onValueChange={(value: ProductFieldType) => updateField(index, 'type', value)}>
                                                    <SelectTrigger>
                                                        <SelectValue placeholder="Select field type" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {fieldTypeOptions.map((option) => (
                                                            <SelectItem key={option.value} value={option.value}>
                                                                {option.label}
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                                <p className="text-xs text-slate-500">
                                                    {fieldTypeOptions.find((option) => option.value === field.type)?.hint}
                                                </p>
                                            </div>
                                            <div className="space-y-2">
                                                <Label className="opacity-0">Required</Label>
                                                <div className="flex h-10 items-center rounded-xl border border-slate-200 bg-white px-3">
                                                    <label className="flex items-center gap-3 text-sm text-slate-700">
                                                        <Checkbox
                                                            checked={field.is_required}
                                                            onCheckedChange={(checked) => updateField(index, 'is_required', Boolean(checked))}
                                                        />
                                                        Required field
                                                    </label>
                                                </div>
                                            </div>
                                            {field.type === 'dropdown' && (
                                                <div className="space-y-2 md:col-span-2">
                                                    <Label>Dropdown options</Label>
                                                    <Input
                                                        value={field.options.join(', ')}
                                                        onChange={(e) => updateDropdownOptions(index, e.target.value)}
                                                        placeholder="Comma separated values, e.g. Bronze, Silver, Gold"
                                                    />
                                                    <p className="text-xs text-slate-500">Enter each selectable option separated by commas.</p>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                ))}
                                {errors.fields && <p className="text-sm text-red-600">{errors.fields}</p>}
                            </CardContent>
                        </Card>
                    </div>

                    <div className="space-y-6">
                        <Card className="rounded-3xl border-slate-200/80 shadow-sm">
                            <CardHeader>
                                <CardTitle className="text-lg">Live customer form preview</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {data.fields.map((field, index) => (
                                    <div key={`preview-${field.id ?? index}`} className="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                        <div className="mb-2 flex items-center justify-between gap-2">
                                            <p className="text-sm font-medium text-slate-900  dark:text-white">{field.label || `Field ${index + 1}`}</p>
                                            {field.is_required && (
                                                <Badge className="border-red-200 bg-red-50 text-red-600" variant="outline">
                                                    Required
                                                </Badge>
                                            )}
                                        </div>
                                        <div className="rounded-xl border border-dashed border-slate-300 bg-white px-3 py-2 text-sm text-slate-400">
                                            {field.type === 'dropdown'
                                                ? field.options.length > 0
                                                    ? `Dropdown: ${field.options.join(', ')}`
                                                    : 'Dropdown options will appear here'
                                                : `${field.type} input preview`}
                                        </div>
                                    </div>
                                ))}
                            </CardContent>
                        </Card>

                        <Card className="rounded-3xl border-slate-200/80 shadow-sm">
                            <CardHeader>
                                <CardTitle className="text-lg">Publishing checklist</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3 text-sm">
                                <div className="flex items-start gap-3 rounded-2xl bg-slate-50 p-4">
                                    <CheckCircle2 className={cn('mt-0.5 h-4 w-4', data.name.trim() ? 'text-emerald-600' : 'text-slate-300')} />
                                    <div>
                                        <p className="font-medium text-slate-900  dark:text-white">Product identity added</p>
                                        <p className="text-slate-500">Use a clear product name and short explanation.</p>
                                    </div>
                                </div>
                                <div className="flex items-start gap-3 rounded-2xl bg-slate-50 p-4">
                                    <CheckCircle2 className={cn('mt-0.5 h-4 w-4', data.cover_duration_options.length > 0 ? 'text-emerald-600' : 'text-slate-300')} />
                                    <div>
                                        <p className="font-medium text-slate-900  dark:text-white">Coverage plans selected</p>
                                        <p className="text-slate-500">At least one duration must be available.</p>
                                    </div>
                                </div>
                                <div className="flex items-start gap-3 rounded-2xl bg-slate-50 p-4">
                                    <CheckCircle2 className={cn('mt-0.5 h-4 w-4', activeFieldCount > 0 ? 'text-emerald-600' : 'text-slate-300')} />
                                    <div>
                                        <p className="font-medium text-slate-900  dark:text-white">Submission fields configured</p>
                                        <p className="text-slate-500">Partners need these fields when submitting customers.</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <Button type="button" className="h-12 w-full bg-slate-900 text-white hover:bg-slate-800" onClick={submit} disabled={!canSubmit || processing}>
                            {processing ? 'Saving product...' : product ? 'Update product' : 'Create product'}
                        </Button>
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}
