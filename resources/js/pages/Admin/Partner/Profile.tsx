import AdminLayout from '@/layouts/AdminLayout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useForm, usePage } from '@inertiajs/react';
import { toast } from 'sonner';
import { Key, Link2 } from 'lucide-react';

interface PartnerInfo {
    id: number;
    name: string;
    partner_code: string;
    contact_email: string;
    contact_phone: string | null;
    company_name: string | null;
    website_url: string | null;
    connected_at: string | null;
    connected_base_url: string | null;
    has_api_key: boolean;
}

interface Props {
    partner: PartnerInfo;
    errors?: Record<string, string>;
}

export default function PartnerProfile({ partner }: Props) {
    const { errors } = usePage<{ errors: Record<string, string> }>().props;

    const { data, setData, patch, processing } = useForm({
        name: partner.name,
        contact_email: partner.contact_email,
        contact_phone: partner.contact_phone ?? '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        patch(route('admin.partner.profile.update'), {
            onSuccess: () => toast.success('Profile updated.'),
            onError: () => toast.error('Failed to update profile.'),
        });
    };

    return (
        <AdminLayout title="My profile">
            <div className="mx-auto max-w-2xl space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-gray-800">Profile</h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        {partner.name} &middot; {partner.partner_code}
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Account info</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3 text-sm">
                        <div className="flex justify-between rounded-lg border px-4 py-3">
                            <span className="text-muted-foreground">Partner code</span>
                            <span className="font-mono text-xs font-medium">{partner.partner_code}</span>
                        </div>
                        <div className="flex justify-between rounded-lg border px-4 py-3">
                            <span className="text-muted-foreground">Company</span>
                            <span className="text-xs">{partner.company_name ?? '-'}</span>
                        </div>
                        <div className="flex justify-between rounded-lg border px-4 py-3">
                            <span className="text-muted-foreground">Website</span>
                            <span className="text-xs">{partner.website_url ?? '-'}</span>
                        </div>
                        <div className="flex justify-between rounded-lg border px-4 py-3">
                            <span className="flex items-center gap-1.5 text-muted-foreground">
                                <Link2 className="h-3.5 w-3.5" /> Connected
                            </span>
                            <span className="text-xs">{partner.connected_at ?? 'Never'}</span>
                        </div>
                        <div className="flex justify-between rounded-lg border px-4 py-3">
                            <span className="flex items-center gap-1.5 text-muted-foreground">
                                <Key className="h-3.5 w-3.5" /> API key
                            </span>
                            <span className={`text-xs font-medium ${partner.has_api_key ? 'text-emerald-600' : 'text-slate-400'}`}>
                                {partner.has_api_key ? 'Active' : 'Not generated'}
                            </span>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Edit profile</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-5">
                            <div>
                                <label className="mb-1 block text-sm font-medium text-gray-700">Name <span className="text-red-500">*</span></label>
                                <input type="text" className="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 outline-none focus:ring-2 focus:ring-[#0e9f84]"
                                    value={data.name} onChange={(e) => setData('name', e.target.value)} />
                                {errors.name && <p className="mt-1 text-sm text-red-500">{errors.name}</p>}
                            </div>
                            <div>
                                <label className="mb-1 block text-sm font-medium text-gray-700">Email <span className="text-red-500">*</span></label>
                                <input type="email" className="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 outline-none focus:ring-2 focus:ring-[#0e9f84]"
                                    value={data.contact_email} onChange={(e) => setData('contact_email', e.target.value)} />
                                {errors.contact_email && <p className="mt-1 text-sm text-red-500">{errors.contact_email}</p>}
                            </div>
                            <div>
                                <label className="mb-1 block text-sm font-medium text-gray-700">Phone</label>
                                <input type="text" className="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 outline-none focus:ring-2 focus:ring-[#0e9f84]"
                                    value={data.contact_phone} onChange={(e) => setData('contact_phone', e.target.value)} />
                                {errors.contact_phone && <p className="mt-1 text-sm text-red-500">{errors.contact_phone}</p>}
                            </div>
                            <div className="flex justify-end">
                                <Button type="submit" disabled={processing} className="bg-[#0e9f84] text-white hover:bg-[#0c8f77]">
                                    {processing ? 'Saving...' : 'Save changes'}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AdminLayout>
    );
}
