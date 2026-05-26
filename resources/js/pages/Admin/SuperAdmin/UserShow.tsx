import AdminLayout from '@/layouts/AdminLayout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { router, Link } from '@inertiajs/react';
import { Pencil, UserX, Trash2, Shield } from 'lucide-react';
import { toast } from 'sonner';

interface UserData {
    id: number;
    name: string;
    email: string;
    is_active: boolean;
    created_at: string;
    roles: { id: number; name: string }[];
}

const ROLE_COLORS: Record<string, string> = {
    super_admin: 'bg-purple-100 text-purple-700 ring-purple-200',
    reconciliation_admin: 'bg-blue-100 text-blue-700 ring-blue-200',
    customer_service: 'bg-emerald-100 text-emerald-700 ring-emerald-200',
};

export default function UserShow(
    { user }:
    { user: UserData }
) {
    const currentRole = user.roles[0]?.name ?? '';

    return (
        <AdminLayout title="User details">
            <div className="mx-auto max-w-4xl space-y-6">
                <div className="flex items-start justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-800">{user.name}</h1>
                        <p className="text-sm text-muted-foreground">{user.email}</p>
                    </div>
                    <div className="flex gap-2">
                        <Link href={route('admin.users.edit', user.id)}>
                            <Button size="sm" variant="outline">
                                <Pencil className="mr-1 h-3.5 w-3.5" /> Edit
                            </Button>
                        </Link>
                        <Button size="sm" variant="outline" onClick={() => {
                            if (confirm('Deactivate this user?')) {
                                router.post(route('admin.users.deactivate', user.id), {}, {
                                    preserveScroll: true,
                                    onSuccess: () => toast.success('User deactivated.'),
                                });
                            }
                        }}>
                            <UserX className="mr-1 h-3.5 w-3.5" /> Deactivate
                        </Button>
                        <Button size="sm" variant="outline" onClick={() => {
                            if (confirm('Delete this user? This cannot be undone.')) {
                                router.delete(route('admin.users.destroy', user.id), {
                                    preserveScroll: true,
                                    onSuccess: () => toast.success('User deleted.'),
                                });
                            }
                        }}>
                            <Trash2 className="mr-1 h-3.5 w-3.5" /> Delete
                        </Button>
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Account info</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3 text-sm">
                            <div className="flex justify-between rounded-lg border px-4 py-3">
                                <span className="text-muted-foreground">ID</span>
                                <span className="font-mono text-xs">#{user.id}</span>
                            </div>
                            <div className="flex justify-between rounded-lg border px-4 py-3">
                                <span className="text-muted-foreground">Name</span>
                                <span className="text-xs font-medium">{user.name}</span>
                            </div>
                            <div className="flex justify-between rounded-lg border px-4 py-3">
                                <span className="text-muted-foreground">Email</span>
                                <span className="text-xs">{user.email}</span>
                            </div>
                            <div className="flex justify-between rounded-lg border px-4 py-3">
                                <span className="text-muted-foreground">Status</span>
                                {user.is_active !== false ? (
                                    <span className="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-700">Active</span>
                                ) : (
                                    <span className="rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-700">Inactive</span>
                                )}
                            </div>
                            <div className="flex justify-between rounded-lg border px-4 py-3">
                                <span className="text-muted-foreground">Joined</span>
                                <span className="text-xs">{user.created_at ? new Date(user.created_at).toLocaleDateString() : '-'}</span>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <Shield className="h-4 w-4" />
                                Role
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3 text-sm">
                            <div className="flex justify-between rounded-lg border px-4 py-3">
                                <span className="text-muted-foreground">Current role</span>
                                <span className={`inline-block rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset ${ROLE_COLORS[currentRole] || 'bg-slate-100 text-slate-600 ring-slate-200'}`}>
                                    {currentRole.replaceAll('_', ' ')}
                                </span>
                            </div>
                            {currentRole !== 'super_admin' && (
                                <div className="pt-2">
                                    <Link href={route('admin.users.edit', user.id)}>
                                        <Button size="sm" variant="outline" className="w-full">
                                            Change role
                                        </Button>
                                    </Link>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>


            </div>
        </AdminLayout>
    );
}
