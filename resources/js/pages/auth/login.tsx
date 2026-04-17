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
            <div className="w-full max-w-md space-y-6">
                <div className="space-y-2 text-center lg:text-left">
                    <span className="inline-flex rounded-full border border-primary/20 bg-primary/10 px-3 py-1 text-xs font-semibold tracking-wide text-primary">
                        Admin Portal
                    </span>
                    <h2 className="text-2xl font-bold text-foreground">Sign in</h2>
                    <p className="text-sm text-muted-foreground">Use your admin email and password to continue.</p>
                </div>
                {flash.error && (
                    <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-500/40 dark:bg-red-500/10 dark:text-red-200">
                        {flash.error}
                    </div>
                )}
                <form
                    className="space-y-5 rounded-2xl border border-border/80 bg-card/95 p-6 text-card-foreground shadow-[0_20px_50px_rgba(2,6,23,0.12)] ring-1 ring-primary/5 backdrop-blur md:p-8"
                    onSubmit={(e) => {
                        e.preventDefault();
                        post(route('login'));
                    }}
                >
                    <div className="space-y-2">
                        <Label htmlFor="email">Email</Label>
                        <div className="relative">
                            <Mail className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                id="email"
                                type="email"
                                autoComplete="username"
                                placeholder="admin@company.com"
                                className="h-11 bg-background/80 pl-10"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                            />
                        </div>
                        {errors.email && <p className="text-sm text-red-600">{errors.email}</p>}
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="password">Password</Label>
                        <div className="relative">
                            <Lock className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                id="password"
                                type={showPassword ? 'text' : 'password'}
                                autoComplete="current-password"
                                placeholder="Enter your password"
                                className="h-11 bg-background/80 px-10"
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                            />
                            <button
                                type="button"
                                onClick={() => setShowPassword((prev) => !prev)}
                                className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground transition-colors hover:text-foreground"
                                aria-label={showPassword ? 'Hide password' : 'Show password'}
                            >
                                {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                            </button>
                        </div>
                        {errors.password && <p className="text-sm text-red-600">{errors.password}</p>}
                    </div>
                    <div className="flex items-center justify-between text-sm">
                        <span className="text-muted-foreground">Need help signing in?</span>
                        <Link href={route('password.request')} className="font-medium text-primary hover:underline">
                            Forgot password
                        </Link>
                    </div>
                    <Button type="submit" className="h-11 w-full text-base" disabled={processing}>
                        {processing ? 'Signing in...' : 'Sign in'}
                    </Button>
                </form>
            </div>
        </AuthSplitLayout>
    );
}
