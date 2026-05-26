import AdminLayout from '@/layouts/AdminLayout';
import { Button } from '@/components/ui/button';
import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { toast } from 'sonner';

interface UserData {
    id: number;
    name: string;
    email: string;
    is_active: boolean;
    roles: { id: number; name: string }[];
}

const ROLE_OPTIONS = ['reconciliation_admin', 'customer_service'];

export default function UserEdit({ user }: { user: UserData }) {
    const { errors } = usePage<{ errors: Record<string, string> }>().props;

    const [name, setName] = useState(user.name);
    const [email, setEmail] = useState(user.email);
    const [role, setRole] = useState(user.roles[0]?.name ?? '');
    const [isActive, setIsActive] = useState(user.is_active !== false);
    const [saving, setSaving] = useState(false);

    const currentRoleName = user.roles[0]?.name ?? '';
    const isSuperAdmin = currentRoleName === 'super_admin';

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        setSaving(true);
        const data: Record<string, string | boolean> = {};
        if (name !== user.name) data.name = name;
        if (email !== user.email) data.email = email;
        if (role !== currentRoleName) data.role = role;
        data.is_active = isActive;

        router.patch(route('admin.users.update', user.id), data, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('User updated.');
                setSaving(false);
            },
            onError: (errs) => {
                const msg = Object.values(errs).join('\n');
                toast.error(msg);
                setSaving(false);
            },
        });
    };

    return (
        <AdminLayout title="Edit user">
            <div className="mx-auto max-w-lg rounded-xl border bg-white p-6 shadow-md">
                <h2 className="mb-1 text-xl font-semibold text-gray-800">Edit User</h2>
                <p className="mb-6 text-sm text-gray-500">{user.email}</p>

                <form onSubmit={submit} className="space-y-5">
                    <div>
                        <label className="mb-1 block text-sm font-medium text-gray-700">Name</label>
                        <input type="text" className="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 outline-none focus:ring-2 focus:ring-[#0e9f84]"
                            value={name} onChange={(e) => setName(e.target.value)} />
                        {errors.name && <p className="mt-1 text-sm text-red-500">{errors.name}</p>}
                    </div>

                    <div>
                        <label className="mb-1 block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" className="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 outline-none focus:ring-2 focus:ring-[#0e9f84]"
                            value={email} onChange={(e) => setEmail(e.target.value)} />
                        {errors.email && <p className="mt-1 text-sm text-red-500">{errors.email}</p>}
                    </div>

                    {!isSuperAdmin && (
                        <div>
                            <label className="mb-1 block text-sm font-medium text-gray-700">Role</label>
                            <select className="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 outline-none focus:ring-2 focus:ring-[#0e9f84]"
                                value={role} onChange={(e) => setRole(e.target.value)}>
                                <option value="">Keep current</option>
                                {ROLE_OPTIONS.map((r) => (
                                    <option key={r} value={r}>{r.replaceAll('_', ' ')}</option>
                                ))}
                            </select>
                            {errors.role && <p className="mt-1 text-sm text-red-500">{errors.role}</p>}
                        </div>
                    )}

                    {isSuperAdmin && (
                        <div className="rounded-lg bg-blue-50 px-4 py-3 text-sm text-blue-700">
                            Super admin role cannot be changed.
                        </div>
                    )}

                    <div className="flex items-center gap-3">
                        <label className="text-sm font-medium text-gray-700">Active</label>
                        <input type="checkbox" checked={isActive} onChange={(e) => setIsActive(e.target.checked)}
                            className="h-4 w-4 rounded border-gray-300 text-[#0e9f84] focus:ring-[#0e9f84]" />
                    </div>

                    <div className="flex justify-end gap-3">
                        <Button type="button" variant="outline" onClick={() => window.history.back()}>Cancel</Button>
                        <Button type="submit" disabled={saving} className="bg-[#0e9f84] text-white hover:bg-[#0c8f77]">
                            {saving ? 'Saving...' : 'Save changes'}
                        </Button>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}
