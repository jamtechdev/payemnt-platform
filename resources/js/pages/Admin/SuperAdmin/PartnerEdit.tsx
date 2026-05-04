import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/AdminLayout';
import { useForm } from '@inertiajs/react';
import React from 'react';

interface ProductOption {
    id: number;
    name: string;
}

export default function PartnerEdit({ partner, products = [] }: { partner: any; products?: ProductOption[] }) {
    const { data, setData, patch, processing, errors } = useForm({
        name: partner.name || '',
        contact_email: partner.contact_email || partner.email || '',
        contact_phone: partner.contact_phone || partner.phone || '',
        company_name: partner.company_name || '',
        website_url: partner.website_url || '',
        notes: partner.notes || '',
        status: partner.status || 'active',
        product_ids: Array.isArray(partner.products) ? partner.products.map((p: any) => Number(p.id)) : [],
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        patch(route('admin.partners.update', partner.id));
    };

    return (
        <AdminLayout title="Edit Partner">
            <div className="mx-auto max-w-2xl rounded-xl border bg-white p-6 shadow-md dark:border-gray-700 dark:bg-gray-900">
                <h2 className="mb-2 text-xl font-semibold text-gray-800 dark:text-gray-100">Edit Partner</h2>
                <p className="mb-6 text-sm text-gray-500 dark:text-gray-400">
                    Update partner details. API key management is available on the partner detail page.
                </p>

                <form onSubmit={submit} className="space-y-5">
                    {/* Partner Code — read only */}
                    <div>
                        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Partner Code</label>
                        <input
                            type="text"
                            className="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 font-mono text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400"
                            value={partner.partner_code || '—'}
                            readOnly
                        />
                        <p className="mt-1 text-xs text-gray-400">Partner code cannot be changed after creation.</p>
                    </div>

                    {/* Name */}
                    <div>
                        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Partner Name <span className="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            className="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 outline-none focus:ring-2 focus:ring-[#0e9f84] dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            placeholder="e.g. Acme Insurance Ltd"
                        />
                        {errors.name && <p className="mt-1 text-sm text-red-500">{errors.name}</p>}
                    </div>

                    {/* Email */}
                    <div>
                        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Contact Email <span className="text-red-500">*</span>
                        </label>
                        <input
                            type="email"
                            className="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 outline-none focus:ring-2 focus:ring-[#0e9f84] dark:border-gray-600 dark:bg-gray-800 dark:text-white"
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
                            className="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 outline-none focus:ring-2 focus:ring-[#0e9f84] dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                            value={data.contact_phone}
                            onChange={(e) => setData('contact_phone', e.target.value)}
                            placeholder="e.g. +234 800 000 0000"
                        />
                        {errors.contact_phone && <p className="mt-1 text-sm text-red-500">{errors.contact_phone}</p>}
                    </div>

                    <div>
                        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Company Name</label>
                        <input type="text" className="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 outline-none focus:ring-2 focus:ring-[#0e9f84] dark:border-gray-600 dark:bg-gray-800 dark:text-white" value={data.company_name} onChange={(e) => setData('company_name', e.target.value as any)} />
                    </div>
                    <div>
                        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Website URL</label>
                        <input type="url" className="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 outline-none focus:ring-2 focus:ring-[#0e9f84] dark:border-gray-600 dark:bg-gray-800 dark:text-white" value={data.website_url} onChange={(e) => setData('website_url', e.target.value as any)} />
                    </div>
                    <div>
                        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Notes</label>
                        <textarea className="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 outline-none focus:ring-2 focus:ring-[#0e9f84] dark:border-gray-600 dark:bg-gray-800 dark:text-white" value={data.notes} onChange={(e) => setData('notes', e.target.value as any)} rows={3} />
                    </div>

                    {/* Status */}
                    <div>
                        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                        <select
                            className="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 outline-none focus:ring-2 focus:ring-[#0e9f84] dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                            value={data.status}
                            onChange={(e) => setData('status', e.target.value)}
                        >
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="suspended">Suspended</option>
                        </select>
                        {errors.status && <p className="mt-1 text-sm text-red-500">{errors.status}</p>}
                    </div>

                    <div>
                        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Products partner can sell</label>
                        <select
                            multiple
                            value={data.product_ids.map(String)}
                            onChange={(e) => {
                                const values = Array.from(e.target.selectedOptions).map((option) => Number(option.value));
                                setData('product_ids', values as any);
                            }}
                            className="min-h-32 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 outline-none focus:ring-2 focus:ring-[#0e9f84] dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                        >
                            {products.map((product) => (
                                <option key={product.id} value={product.id}>
                                    {product.name}
                                </option>
                            ))}
                        </select>
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
                            {processing ? 'Updating...' : 'Update Partner'}
                        </Button>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}
