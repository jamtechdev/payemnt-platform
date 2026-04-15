import AuthSplitLayout from '@/components/auth/AuthSplitLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Link, useForm } from '@inertiajs/react';

interface Props {
    token: string;
    email: string;
}

export default function ResetPassword({ token, email }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        token,
        email: email ?? '',
        password: '',
        password_confirmation: '',
    });

    return (
        <AuthSplitLayout
            pageTitle="Reset password"
            title="Set a new password"
            subtitle="Choose a secure password to protect your admin access."
        >
            <div className="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
                <h1 className="text-2xl font-bold text-slate-900">Reset password</h1>
                <p className="mt-2 text-sm text-slate-600">Choose a secure password for your admin account.</p>

                <form
                    className="mt-5 space-y-4"
                    onSubmit={(e) => {
                        e.preventDefault();
                        post(route('password.store'));
                    }}
                >
                    <input type="hidden" value={data.token} onChange={() => null} />
                    <div className="space-y-2">
                        <Label htmlFor="email">Email</Label>
                        <Input id="email" type="email" autoComplete="email" value={data.email} onChange={(e) => setData('email', e.target.value)} />
                        {errors.email && <p className="text-sm text-red-600">{errors.email}</p>}
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="password">New password</Label>
                        <Input id="password" type="password" autoComplete="new-password" value={data.password} onChange={(e) => setData('password', e.target.value)} />
                        {errors.password && <p className="text-sm text-red-600">{errors.password}</p>}
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="password_confirmation">Confirm password</Label>
                        <Input id="password_confirmation" type="password" autoComplete="new-password" value={data.password_confirmation} onChange={(e) => setData('password_confirmation', e.target.value)} />
                    </div>
                    <Button type="submit" className="w-full bg-emerald-600 hover:bg-emerald-700" disabled={processing}>
                        {processing ? 'Updating...' : 'Update password'}
                    </Button>
                </form>

                <Link href={route('login')} className="mt-4 inline-block text-sm font-medium text-emerald-700 hover:underline">
                    Back to sign in
                </Link>
            </div>
        </AuthSplitLayout>
    );
}
