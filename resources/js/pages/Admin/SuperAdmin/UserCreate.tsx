import AdminLayout from '@/layouts/AdminLayout';
import { Button } from '@/components/ui/button';
import { useForm } from '@inertiajs/react';
import { toast } from 'sonner';

const ROLE_OPTIONS = ['reconciliation_admin', 'customer_service'];

export default function UserCreate() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        role: 'customer_service',
        password: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('admin.users.store'), {
            onError: (errs) => {
                const messages = Object.values(errs).join('\n');
                toast.error(messages, { id: 'validation-error' });
            },
        });
    };

    return (
        <AdminLayout title="Add user">
            <div className="mx-auto max-w-lg rounded-xl border bg-white p-6 shadow-md">
                <h2 className="mb-2 text-xl font-semibold text-gray-800">Add New User</h2>
                <p className="mb-6 text-sm text-gray-500">Create a new admin user with role-based access.</p>

                <form onSubmit={submit} className="space-y-5">
                    <div>
                        <label className="mb-1 block text-sm font-medium text-gray-700">Name <span className="text-red-500">*</span></label>
                        <input type="text" className="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 outline-none focus:ring-2 focus:ring-[#0e9f84]"
                            value={data.name} onChange={(e) => setData('name', e.target.value)} placeholder="Full name" />
                        {errors.name && <p className="mt-1 text-sm text-red-500">{errors.name}</p>}
                    </div>

                    <div>
                        <label className="mb-1 block text-sm font-medium text-gray-700">Email <span className="text-red-500">*</span></label>
                        <input type="email" className="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 outline-none focus:ring-2 focus:ring-[#0e9f84]"
                            value={data.email} onChange={(e) => setData('email', e.target.value)} placeholder="email@example.com" />
                        {errors.email && <p className="mt-1 text-sm text-red-500">{errors.email}</p>}
                    </div>

                    <div>
                        <label className="mb-1 block text-sm font-medium text-gray-700">Role <span className="text-red-500">*</span></label>
                        <select className="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 outline-none focus:ring-2 focus:ring-[#0e9f84]"
                            value={data.role} onChange={(e) => setData('role', e.target.value)}>
                            {ROLE_OPTIONS.map((r) => (
                                <option key={r} value={r}>{r.replaceAll('_', ' ')}</option>
                            ))}
                        </select>
                        {errors.role && <p className="mt-1 text-sm text-red-500">{errors.role}</p>}
                    </div>

                    <div>
                        <label className="mb-1 block text-sm font-medium text-gray-700">Password <span className="text-red-500">*</span></label>
                        <input type="password" className="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 outline-none focus:ring-2 focus:ring-[#0e9f84]"
                            value={data.password} onChange={(e) => setData('password', e.target.value)} placeholder="Min 12 chars, mixed case, numbers & symbols" />
                        <p className="mt-1 text-xs text-gray-400">Minimum 12 characters with uppercase, lowercase, numbers and symbols.</p>
                        {errors.password && <p className="mt-1 text-sm text-red-500">{errors.password}</p>}
                    </div>

                    <div className="flex justify-end gap-3">
                        <Button type="button" variant="outline" onClick={() => window.history.back()}>Cancel</Button>
                        <Button type="submit" disabled={processing} className="bg-[#0e9f84] text-white hover:bg-[#0c8f77]">
                            {processing ? 'Creating...' : 'Create User'}
                        </Button>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}
