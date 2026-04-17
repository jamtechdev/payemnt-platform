import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/AdminLayout';
import { useForm } from '@inertiajs/react';

export default function PartnerEdit({ partner }: any) {
    const { data, setData, patch, processing, errors } = useForm({
        name: partner.name || '',
        email: partner.email || '',
        phone: partner.phone || '',
        status: partner.status || 'active',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        patch(route('admin.partners.update', partner.id));
    };

    return (
        <AdminLayout title="Edit Partner">
            <div className="mx-auto max-w-2xl rounded-xl border bg-white p-6 shadow-md dark:border-gray-700 dark:bg-gray-900">
                <h2 className="mb-6 text-xl font-semibold text-gray-800 dark:text-gray-100">Edit Partner</h2>

                <form onSubmit={submit} className="space-y-5">
                    {/* Name */}
                    <div>
                        <label className="mb-1 block text-sm text-gray-700 dark:text-gray-300">Name</label>
                        <input
                            type="text"
                            className="w-full rounded-lg border bg-white px-3 py-2 text-gray-900 outline-none focus:ring-2 focus:ring-[#0e9f84] dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                        />
                        {errors.name && <p className="text-sm text-red-500">{errors.name}</p>}
                    </div>

                    {/* Email */}
                    <div>
                        <label className="mb-1 block text-sm text-gray-700 dark:text-gray-300">Email</label>
                        <input
                            type="email"
                            className="w-full rounded-lg border bg-white px-3 py-2 text-gray-900 outline-none focus:ring-2 focus:ring-[#0e9f84] dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                        />
                        {errors.email && <p className="text-sm text-red-500">{errors.email}</p>}
                    </div>

                    {/* Phone */}
                    <div>
                        <label className="mb-1 block text-sm text-gray-700 dark:text-gray-300">Phone</label>
                        <input
                            type="text"
                            className="w-full rounded-lg border bg-white px-3 py-2 text-gray-900 outline-none focus:ring-2 focus:ring-[#0e9f84] dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                            value={data.phone}
                            onChange={(e) => setData('phone', e.target.value)}
                        />
                    </div>

                    {/* Status */}
                    <div>
                        <label className="mb-1 block text-sm text-gray-700 dark:text-gray-300">Status</label>
                        <select
                            className="w-full rounded-lg border bg-white px-3 py-2 text-gray-900 outline-none focus:ring-2 focus:ring-[#0e9f84] dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                            value={data.status}
                            onChange={(e) => setData('status', e.target.value)}
                        >
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>

                    {/* Button */}
                    <div className="flex justify-end">
                        <Button type="submit" disabled={processing} className="bg-[#0e9f84] text-white hover:bg-[#0c8f77]">
                            {processing ? 'Updating...' : 'Update Partner'}
                        </Button>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}
