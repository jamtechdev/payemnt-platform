import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/AdminLayout';
import { useForm } from '@inertiajs/react';
import React from 'react';

interface ProductOption {
    id: number;
    name: string;
}

export default function PartnerCreate({ products = [] }: { products?: ProductOption[] }) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        contact_email: '',
        contact_phone: '',
        partner_code: '',
        product_ids: [] as number[],
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('admin.partners.store'));
    };

    return (
        <AdminLayout title="Create Partner">
            <div className="mx-auto max-w-2xl rounded-xl border bg-white p-6 shadow-md dark:border-gray-700 dark:bg-gray-900">
                <h2 className="mb-2 text-xl font-semibold text-gray-800 dark:text-gray-100">Create New Partner</h2>
                <p className="mb-6 text-sm text-gray-500 dark:text-gray-400">
                    Partners authenticate via API key — no password needed. Generate an API key from the partner detail page after creation.
                </p>

                <form onSubmit={submit} className="space-y-5">
                    {/* Name */}
                    <div>
                        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Partner Name <span className="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            className="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 outline-none focus:ring-2 focus:ring-[#0e9f84] dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            placeholder="e.g. Acme Insurance Ltd"
                        />
                        {errors.name && <p className="mt-1 text-sm text-red-500">{errors.name}</p>}
                    </div>

                    {/* Partner Code */}
                    <div>
                        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Partner Code <span className="text-xs text-gray-400">(optional — auto-generated if blank)</span>
                        </label>
                        <input
                            type="text"
                            className="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 font-mono text-gray-900 uppercase outline-none focus:ring-2 focus:ring-[#0e9f84] dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                            value={data.partner_code}
                            onChange={(e) => setData('partner_code', e.target.value.toUpperCase())}
                            placeholder="e.g. ACME_PARTNER"
                        />
                        <p className="mt-1 text-xs text-gray-400">Used as unique identifier in API calls. Only letters, numbers and underscores.</p>
                        {errors.partner_code && <p className="mt-1 text-sm text-red-500">{errors.partner_code}</p>}
                    </div>

                    {/* Email */}
                    <div>
                        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Contact Email <span className="text-red-500">*</span>
                        </label>
                        <input
                            type="email"
                            className="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 outline-none focus:ring-2 focus:ring-[#0e9f84] dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                            value={data.contact_email}
                            onChange={(e) => setData('contact_email', e.target.value)}
                            placeholder="e.g. admin@acme.com"
                        />
                        {errors.contact_email && <p className="mt-1 text-sm text-red-500">{errors.contact_email}</p>}
                    </div>

                    {/* Phone */}
                    <div>
                        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Contact Phone</label>
                        <input
                            type="text"
                            className="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 outline-none focus:ring-2 focus:ring-[#0e9f84] dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                            value={data.contact_phone}
                            onChange={(e) => setData('contact_phone', e.target.value)}
                            placeholder="e.g. +234 800 000 0000"
                        />
                        {errors.contact_phone && <p className="mt-1 text-sm text-red-500">{errors.contact_phone}</p>}
                    </div>

                    <div>
                        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Products partner can sell</label>
                        <select
                            multiple
                            value={data.product_ids.map(String)}
                            onChange={(e) => {
                                const values = Array.from(e.target.selectedOptions).map((option) => Number(option.value));
                                setData('product_ids', values);
                            }}
                            className="min-h-32 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 outline-none focus:ring-2 focus:ring-[#0e9f84] dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                        >
                            {products.map((product) => (
                                <option key={product.id} value={product.id}>
                                    {product.name}
                                </option>
                            ))}
                        </select>
                        <p className="mt-1 text-xs text-gray-400">Hold Ctrl/Cmd to select multiple products.</p>
                    </div>

                    <div className="rounded-lg border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-700 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-300">
                        💡 After creating the partner, go to their detail page to generate an API key for authentication.
                    </div>

                    <div className="flex justify-end gap-3">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => window.history.back()}
                        >
                            Cancel
                        </Button>
                        <Button type="submit" disabled={processing} className="bg-[#0e9f84] text-white hover:bg-[#0c8f77]">
                            {processing ? 'Creating...' : 'Create Partner'}
                        </Button>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}
