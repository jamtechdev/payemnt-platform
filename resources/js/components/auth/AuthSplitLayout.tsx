import { ReactNode } from 'react';
import { Head } from '@inertiajs/react';

interface AuthSplitLayoutProps {
    title: string;
    subtitle: string;
    pageTitle: string;
    children: ReactNode;
}

export default function AuthSplitLayout({ title, subtitle, pageTitle, children }: AuthSplitLayoutProps) {
    return (
        <div className="flex min-h-screen bg-gradient-to-br from-slate-50 via-emerald-50/30 to-sky-100/40 dark:from-slate-950 dark:via-slate-900 dark:to-slate-950">
            <Head title={pageTitle} />
            <div className="relative hidden w-[44%] flex-col justify-between overflow-hidden border-r border-border/60 p-10 text-white lg:flex">
                <div className="absolute inset-0 bg-[radial-gradient(circle_at_18%_20%,rgba(45,212,191,0.38)_0%,transparent_40%),radial-gradient(circle_at_82%_22%,rgba(59,130,246,0.30)_0%,transparent_35%),linear-gradient(145deg,#072a2c_0%,#0c2344_46%,#111827_100%)]" />
                <div className="absolute -left-24 top-16 h-56 w-56 rounded-full bg-teal-300/20 blur-3xl" />
                <div className="absolute -right-24 bottom-16 h-56 w-56 rounded-full bg-blue-300/20 blur-3xl" />
                <div className="relative z-10 m-auto rounded-2xl border border-white/15 bg-white/5 p-8 shadow-2xl backdrop-blur-sm">
                    <p className="text-sm font-semibold uppercase tracking-[0.2em] text-teal-200">Operations</p>
                    <h1 className="mt-4 max-w-md text-4xl font-bold leading-tight">{title}</h1>
                    <p className="mt-4 max-w-sm text-sm leading-relaxed text-slate-200/90">{subtitle}</p>
                </div>
                <p className="relative z-10 text-xs text-slate-300/70">Authorized personnel only.</p>
            </div>
            <div className="flex flex-1 items-center justify-center p-6 lg:p-10">{children}</div>
        </div>
    );
}
