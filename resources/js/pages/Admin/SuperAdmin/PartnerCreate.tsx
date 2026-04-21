import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/AdminLayout';
import { useForm } from '@inertiajs/react';
import React from 'react';

export default function PartnerCreate() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        phone: '',
        partner_code: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('admin.partners.store'));
    };

    return (
        <AdminLayout title="Create Partner">
            <div className="mx-auto max-w-2xl rounded-xl border bg-white p-6 shadow-md dark:border-gray-700 dark:bg-gray-900">
                <h2 className="mb-6 text-xl font-semibold text-gray-800 dark:text-gray-100">Create New Partner</h2>

                <form onSubmit={submit} className="space-y-5">
                    {/* Name */}
                    <div>
                        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                        <input
                            type="text"
                            className="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 outline-none focus:ring-2 focus:ring-[#0e9f84] dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
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
                            className="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 outline-none focus:ring-2 focus:ring-[#0e9f84] dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                            value={data.partner_code}
                            onChange={(e) => setData('partner_code', e.target.value.toUpperCase())}
                            placeholder="e.g. ACME_PARTNER"
                        />
                        {errors.partner_code && <p className="mt-1 text-sm text-red-500">{errors.partner_code}</p>}
                    </div>

                    {/* Email */}
                    <div>
                        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                        <input
                            type="email"
                            className="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 outline-none focus:ring-2 focus:ring-[#0e9f84] dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                        />
                        {errors.email && <p className="mt-1 text-sm text-red-500">{errors.email}</p>}
                    </div>

                    {/* Phone */}
                    <div>
                        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Phone</label>
                        <input
                            type="text"
                            className="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 outline-none focus:ring-2 focus:ring-[#0e9f84] dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                            value={data.phone}
                            onChange={(e) => setData('phone', e.target.value)}
                        />
                        {errors.phone && <p className="mt-1 text-sm text-red-500">{errors.phone}</p>}
                    </div>

                    {/* Button */}
                    <div className="flex justify-end">
                        <Button type="submit" disabled={processing} className="rounded-lg bg-[#0e9f84] px-6 py-2 text-white hover:bg-[#0c8f77]">
                            {processing ? 'Creating...' : 'Create Partner'}
                        </Button>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}
