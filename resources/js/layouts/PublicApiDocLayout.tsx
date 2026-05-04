import { ReactNode } from 'react';

interface PublicApiDocLayoutProps {
    title: string;
    children: ReactNode;
}

/**
 * Full-width public documentation shell — no admin sidebar, no auth required.
 * Used for /admin/super-admin/api-documentation.
 */
export default function PublicApiDocLayout({ title, children }: PublicApiDocLayoutProps) {
    return (
        <div className="min-h-screen bg-slate-50 text-slate-900">
            <header className="sticky top-0 z-20 border-b border-slate-200/90 bg-white/95 shadow-sm backdrop-blur">
                <div className="mx-auto max-w-5xl px-4 py-5 lg:max-w-6xl lg:px-8">
                    <p className="text-xs font-semibold uppercase tracking-wider text-emerald-700">Insurtech</p>
                    <h1 className="mt-1 text-2xl font-bold tracking-tight text-slate-900">{title}</h1>
                    <p className="mt-2 max-w-3xl text-sm text-slate-600">
                        Public partner integration guide. You do not need to sign in to read this page.
                    </p>
                </div>
            </header>
            <main className="mx-auto max-w-5xl px-4 py-8 pb-16 lg:max-w-6xl lg:px-8">{children}</main>
        </div>
    );
}
