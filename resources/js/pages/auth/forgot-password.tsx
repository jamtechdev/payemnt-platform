import AuthSplitLayout from '@/components/auth/AuthSplitLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Link, useForm, usePage } from '@inertiajs/react';
import { PageProps } from '@/Types';

export default function ForgotPassword() {
    const { flash } = usePage<PageProps>().props;
    const { data, setData, post, processing, errors } = useForm({ email: '' });

    return (
        <AuthSplitLayout
            pageTitle="Forgot password"
            title="Account recovery"
            subtitle="Reset access safely and continue managing partner operations."
        >
            <div className="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
                <h1 className="text-2xl font-bold text-slate-900">Forgot password</h1>
                <p className="mt-2 text-sm text-slate-600">Enter your admin email and we will send a reset link.</p>

                {flash.success && <div className="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-700">{flash.success}</div>}

                <form
                    className="mt-5 space-y-4"
                    onSubmit={(e) => {
                        e.preventDefault();
                        post(route('password.email'));
                    }}
                >
                    <div className="space-y-2">
                        <Label htmlFor="email">Email</Label>
                        <Input id="email" type="email" autoComplete="email" value={data.email} onChange={(e) => setData('email', e.target.value)} placeholder="Enter Email" />
                        {errors.email && <p className="text-sm text-red-600">{errors.email}</p>}
                    </div>
                    <Button type="submit" className="w-full bg-emerald-600 hover:bg-emerald-700" disabled={processing}>
                        {processing ? 'Sending...' : 'Send reset link'}
                    </Button>
                </form>

                <Link href={route('login')} className="mt-4 inline-block text-sm font-medium text-emerald-700 hover:underline">
                    Back to sign in
                </Link>
            </div>
        </AuthSplitLayout>
    );
}
