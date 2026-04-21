import { useForm, usePage } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AdminLayout from '@/layouts/AdminLayout';
import { PageProps } from '@/Types';
import { Layers3, ShieldCheck, Sparkles } from 'lucide-react';

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
const defaultBeneficiaryFields: ProductFieldForm[] = [
    { name: 'beneficiary_first_name', label: 'Beneficiary First Name', type: 'text', is_required: true, options: [] },
    { name: 'beneficiary_surname', label: 'Beneficiary Surname', type: 'text', is_required: true, options: [] },
    { name: 'beneficiary_date_of_birth', label: 'Beneficiary Date of Birth', type: 'date', is_required: true, options: [] },
    { name: 'beneficiary_age', label: 'Beneficiary Age (Auto)', type: 'number', is_required: false, options: [] },
    { name: 'beneficiary_gender', label: 'Beneficiary Gender', type: 'dropdown', is_required: true, options: ['male', 'female', 'other'] },
    { name: 'beneficiary_address', label: 'Beneficiary Address', type: 'text', is_required: true, options: [] },
    { name: 'cover_start_date', label: 'Cover Start Date', type: 'date', is_required: true, options: [] },
    { name: 'cover_duration', label: 'Cover Duration', type: 'dropdown', is_required: true, options: ['monthly', 'annual'] },
];

function beneficiaryTemplateFields(): ProductFieldForm[] {
    return defaultBeneficiaryFields.map((field) => ({ ...field, options: [...field.options] }));
}

function normalizeFields(fields?: ProductPayload['fields']): ProductFieldForm[] {
    if (!Array.isArray(fields) || fields.length === 0) {
        return beneficiaryTemplateFields();
    }

    const byName = new Map<string, ProductFieldForm>();
    fields.forEach((field) => {
        const key = String(field.name ?? '');
        if (key) {
            byName.set(key, {
                id: field.id,
                name: key,
                label: String(field.label ?? ''),
                type: (field.type as ProductFieldType) || 'text',
                is_required: Boolean(field.is_required),
                options: Array.isArray(field.options) ? field.options.filter(Boolean) : [],
            });
        }
    });

    return beneficiaryTemplateFields().map((template) => {
        const existing = byName.get(template.name);
        if (!existing) return template;
        return {
            ...template,
            id: existing.id,
            label: existing.label || template.label,
            options: existing.type === 'dropdown' && existing.options.length > 0 ? existing.options : template.options,
        };
    });
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
        name: product?.name ?? 'Nigerian Beneficiary Community Product',
        description: product?.description ?? 'Insuretech beneficiary product template.',
        status: product?.status ?? 'active',
        cover_duration_options: [1, 12],
        fields: product ? normalizeFields(product?.fields) : beneficiaryTemplateFields(),
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

    const applyBeneficiaryTemplate = () => {
        setData('fields', beneficiaryTemplateFields());
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

    return (
        <AdminLayout title={product ? 'Edit Product' : 'Create Product'}>
            <div className="space-y-6">
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
                                <div className="rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-4">
                                    <p className="text-sm font-medium text-slate-700">Fixed duration options</p>
                                    <div className="mt-3 flex flex-wrap gap-2">
                                        {data.cover_duration_options.map((months) => (
                                            <span key={months} className="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700">
                                                {months === 1 ? 'Monthly' : months === 12 ? 'Annual' : `${months} months`}
                                            </span>
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
                                            <p className="text-sm text-slate-500">These are the fields partner APIs must send for this product.</p>
                                        </div>
                                    </div>
                                    <Button type="button" variant="outline" onClick={applyBeneficiaryTemplate}>
                                        Use Beneficiary Template
                                    </Button>
                                </div>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {data.fields.map((field, index) => (
                                    <div key={`${field.id ?? 'new'}-${index}`} className="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                                        <div className="mb-4 flex items-center justify-between gap-3">
                                            <div>
                                                <p className="text-sm font-semibold text-slate-900">{field.label || `Field ${index + 1}`}</p>
                                                <p className="text-xs text-slate-500">API key: {field.name || `field_${index + 1}`}</p>
                                            </div>
                                            <Badge variant="outline">Fixed field</Badge>
                                        </div>
                                        <div className="grid gap-4 md:grid-cols-2">
                                            <div className="space-y-2">
                                                <Label>Display label</Label>
                                                <Input
                                                    value={field.label}
                                                    onChange={(e) => updateField(index, 'label', e.target.value)}
                                                    placeholder="e.g. Beneficiary First Name"
                                                />
                                            </div>
                                            <div className="space-y-2">
                                                <Label>API field key</Label>
                                                <Input
                                                    value={field.name}
                                                    readOnly
                                                    placeholder="e.g. beneficiary_first_name"
                                                />
                                            </div>
                                            <div className="space-y-2">
                                                <Label>Field type</Label>
                                                <Select value={field.type} disabled>
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
                                                            disabled
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
                        <Button type="button" className="h-12 w-full bg-slate-900 text-white hover:bg-slate-800" onClick={submit} disabled={!canSubmit || processing}>
                            {processing ? 'Saving product...' : product ? 'Update product' : 'Create product'}
                        </Button>
                </div>
            </div>
        </AdminLayout>
    );
}
