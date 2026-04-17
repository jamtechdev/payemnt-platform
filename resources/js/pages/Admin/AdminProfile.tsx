import AdminLayout from '@/layouts/AdminLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { PageProps } from '@/Types';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler, useEffect, useState } from 'react';

interface ApiToken {
    id: number;
    name: string;
    last_used_at: string | null;
    created_at: string;
}

interface Props extends PageProps {
    profile: {
        job_title: string | null;
        phone: string | null;
        timezone: string | null;
    };
    apiTokens: ApiToken[];
}

export default function AdminProfile({ profile, apiTokens }: Props) {
    const { auth, flash } = usePage<Props>().props;
    const [tokenVisible, setTokenVisible] = useState(!!flash.new_api_token);
    const initials = (auth.user?.name ?? 'U')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase())
        .join('');

    useEffect(() => {
        setTokenVisible(!!flash.new_api_token);
    }, [flash.new_api_token]);

    const { data, setData, patch, processing, errors } = useForm({
        name: auth.user?.name ?? '',
        email: auth.user?.email ?? '',
        job_title: profile.job_title ?? '',
        phone: profile.phone ?? '',
        timezone: profile.timezone ?? '',
        avatar: null as File | null,
        remove_avatar: false,
    });

    const tokenForm = useForm({ token_name: '' });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        patch(route('admin.profile.update'), { forceFormData: true });
    };

    return (
        <AdminLayout title="My profile">
            <Head title="My profile" />
            {flash.new_api_token && tokenVisible && (
                <div className="mb-6 rounded-xl border border-amber-300/60 bg-amber-50 p-4 text-amber-950 shadow-sm dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-100">
                    <p className="mb-2 text-sm font-medium">New API token (copy and store securely)</p>
                    <code className="block break-all rounded-md border border-amber-200/80 bg-white/90 p-2 text-xs dark:border-amber-300/20 dark:bg-black/20">
                        {flash.new_api_token}
                    </code>
                    <Button type="button" variant="outline" size="sm" className="mt-3" onClick={() => setTokenVisible(false)}>
                        Dismiss
                    </Button>
                </div>
            )}
            <div className="w-full space-y-6">
                <div className="rounded-xl border border-border bg-card p-4 shadow-sm md:p-5">
                    <div className="flex flex-wrap items-center justify-between gap-4">
                        <div className="flex items-center gap-3">
                            <div className="flex h-12 w-12 items-center justify-center rounded-full bg-primary/10 text-sm font-semibold text-primary">{initials}</div>
                            <div>
                                <h1 className="text-lg font-semibold text-foreground">{auth.user?.name ?? 'User'}</h1>
                                <p className="text-sm text-muted-foreground">{auth.user?.email}</p>
                            </div>
                        </div>
                        <span className="rounded-full border border-border bg-muted px-3 py-1 text-xs font-medium capitalize text-muted-foreground">
                            {String(auth.role).replace('_', ' ')}
                        </span>
                    </div>
                </div>

                <div className="rounded-xl border border-border bg-card p-5 shadow-sm md:p-6">
                    <div className="mb-6">
                        <h2 className="text-lg font-semibold text-foreground">Profile information</h2>
                        <p className="text-sm text-muted-foreground">Update your account details and profile photo.</p>
                    </div>
                    <form onSubmit={submit} className="space-y-5">
                        <div className="grid gap-5 md:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="name">Name</Label>
                                <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} autoComplete="name" />
                                {errors.name && <p className="text-sm text-red-600">{errors.name}</p>}
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="email">Email</Label>
                                <Input id="email" type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} autoComplete="email" />
                                {errors.email && <p className="text-sm text-red-600">{errors.email}</p>}
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="job_title">Job title</Label>
                                <Input id="job_title" value={data.job_title ?? ''} onChange={(e) => setData('job_title', e.target.value)} />
                                {errors.job_title && <p className="text-sm text-red-600">{errors.job_title}</p>}
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="phone">Phone</Label>
                                <Input id="phone" value={data.phone ?? ''} onChange={(e) => setData('phone', e.target.value)} />
                                {errors.phone && <p className="text-sm text-red-600">{errors.phone}</p>}
                            </div>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="timezone">Timezone</Label>
                            <Input id="timezone" placeholder="e.g. UTC" value={data.timezone ?? ''} onChange={(e) => setData('timezone', e.target.value)} />
                            {errors.timezone && <p className="text-sm text-red-600">{errors.timezone}</p>}
                        </div>
                        <div className="rounded-lg border border-dashed border-border/80 bg-muted/20 p-4">
                            <div className="space-y-2">
                                <Label htmlFor="avatar">Profile photo</Label>
                                <Input id="avatar" type="file" accept="image/*" onChange={(e) => setData('avatar', e.target.files?.[0] ?? null)} />
                                {errors.avatar && <p className="text-sm text-red-600">{errors.avatar}</p>}
                                <label className="flex items-center gap-2 text-sm text-muted-foreground">
                                    <input type="checkbox" className="h-4 w-4 accent-primary" checked={data.remove_avatar} onChange={(e) => setData('remove_avatar', e.target.checked)} />
                                    Remove current photo
                                </label>
                            </div>
                        </div>
                        <div className="pt-2">
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Saving…' : 'Save changes'}
                            </Button>
                        </div>
                    </form>
                </div>

                {auth.role === 'super_admin' && (
                    <div className="rounded-xl border border-border bg-card p-5 shadow-sm md:p-6">
                        <h2 className="mb-2 text-lg font-semibold text-foreground">API tokens (Sanctum)</h2>
                        <p className="mb-5 text-sm text-muted-foreground">Create bearer tokens for automation. Partner ingestion still uses partner API keys.</p>
                        <form
                            className="mb-6 flex flex-wrap items-end gap-3"
                            onSubmit={(e) => {
                                e.preventDefault();
                                tokenForm.post(route('admin.personal-access-tokens.store'), {
                                    preserveScroll: true,
                                    onSuccess: () => tokenForm.reset('token_name'),
                                });
                            }}
                        >
                            <div className="min-w-[220px] flex-1 space-y-2">
                                <Label htmlFor="token_name">Token name</Label>
                                <Input id="token_name" value={tokenForm.data.token_name} onChange={(e) => tokenForm.setData('token_name', e.target.value)} placeholder="e.g. CI export job" />
                                {tokenForm.errors.token_name && <p className="text-sm text-red-600">{tokenForm.errors.token_name}</p>}
                            </div>
                            <Button type="submit" disabled={tokenForm.processing}>
                                Create token
                            </Button>
                        </form>
                        <ul className="divide-y divide-border rounded-lg border border-border bg-background/40">
                            {apiTokens.length === 0 && <li className="p-4 text-sm text-muted-foreground">No tokens yet.</li>}
                            {apiTokens.map((t) => (
                                <li key={t.id} className="flex flex-wrap items-center justify-between gap-3 p-4 text-sm">
                                    <div>
                                        <span className="font-medium text-foreground">{t.name}</span>
                                        <span className="ml-2 text-muted-foreground">created {new Date(t.created_at).toLocaleString()}</span>
                                        <span className="ml-2 text-muted-foreground">last used {t.last_used_at ? new Date(t.last_used_at).toLocaleString() : 'never'}</span>
                                    </div>
                                    <Button
                                        type="button"
                                        variant="destructive"
                                        size="sm"
                                        onClick={() => {
                                            if (confirm('Revoke this token?')) {
                                                router.delete(route('admin.personal-access-tokens.destroy', t.id), { preserveScroll: true });
                                            }
                                        }}
                                    >
                                        Revoke
                                    </Button>
                                </li>
                            ))}
                        </ul>
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}
