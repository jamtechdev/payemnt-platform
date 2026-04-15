import AuthSplitLayout from '@/components/auth/AuthSplitLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Link, useForm, usePage } from '@inertiajs/react';
import { PageProps } from '@/Types';
import { Eye, EyeOff, Lock, Mail } from 'lucide-react';
import { useState } from 'react';

export default function Login() {
    const { flash } = usePage<PageProps>().props;
    const { data, setData, post, processing, errors } = useForm({ email: '', password: '' });
    const [showPassword, setShowPassword] = useState(false);

    return (
        <AuthSplitLayout
            pageTitle="Sign in"
            title="Partner-distributed product sales"
            subtitle="Monitor partners, customers, and revenue in one secure admin workspace."
        >
            <div className="w-full max-w-md space-y-8">
                <div className="hidden lg:block">
                    <h2 className="text-2xl font-bold text-slate-900 dark:text-slate-200">Sign in</h2>
                    <p className="mt-1 text-sm text-slate-600">Use your admin email and password to continue.</p>
                </div>
                {flash.error && <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{flash.error}</div>}
                <form
                    className="space-y-5 rounded-2xl border border-[#cdebe4]  border-slate-200 bg-white/95 p-8 shadow-[0_20px_50px_rgba(2,6,23,0.08)] backdrop-blur dark:bg-gray-800/95"
                    onSubmit={(e) => {
                        e.preventDefault();
                        post(route('login'));
                    }}
                >
                    <div className="space-y-2">
                        <Label htmlFor="email">Email</Label>
                        <div className="relative">
                            <Mail className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                            <Input
                                id="email"
                                type="email"
                                autoComplete="username"
                                className="h-11 border-slate-200 pl-10 focus-visible:ring-[#0e9f84]"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                            />
                        </div>
                        {errors.email && <p className="text-sm text-red-600">{errors.email}</p>}
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="password">Password</Label>
                        <div className="relative">
                            <Lock className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                            <Input
                                id="password"
                                type={showPassword ? 'text' : 'password'}
                                autoComplete="current-password"
                                className="h-11 border-slate-200 px-10 focus-visible:ring-[#0e9f84]"
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                            />
                            <button
                                type="button"
                                onClick={() => setShowPassword((prev) => !prev)}
                                className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-700"
                                aria-label={showPassword ? 'Hide password' : 'Show password'}
                            >
                                {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                            </button>
                        </div>
                        {errors.password && <p className="text-sm text-red-600">{errors.password}</p>}
                    </div>
                    <div className="flex items-center justify-between text-sm">
                        <span className="text-slate-500">Need help signing in?</span>
                        <Link href={route('password.request')} className="font-medium text-[#0e9f84] hover:underline">
                            Forgot password
                        </Link>
                    </div>
                    <Button type="submit" className="h-11 w-full bg-[#0e9f84] text-base text-white hover:bg-[#0c8f77]" disabled={processing}>
                        {processing ? 'Signing in...' : 'Sign in'}
                    </Button>
                </form>
            </div>
        </AuthSplitLayout>
    );
}
