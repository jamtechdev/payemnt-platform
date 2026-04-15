import { usePage, Link } from '@inertiajs/react';
import AdminLayout from '@/layouts/AdminLayout';
import { PageProps } from '@/Types';

export default function Error403() {
    const { auth } = usePage<PageProps>().props;
    const dashboardHref = auth.role === 'super_admin' ? route('admin.platform.dashboard') : auth.role === 'reconciliation_admin' ? route('admin.reports.dashboard') : route('admin.cs.dashboard');

    const content = (
        <div className="mx-auto max-w-xl rounded bg-white p-8 text-center shadow">
            <div className="text-4xl">⛔</div>
            <h1 className="mt-3 text-xl font-semibold">Access denied</h1>
            <p className="mt-2 text-neutral-600">Your role does not have access to this section.</p>
            <Link href={dashboardHref} className="mt-4 inline-block rounded bg-neutral-900 px-4 py-2 text-white">Go to dashboard</Link>
        </div>
    );

    if (auth.user) return <AdminLayout title="403">{content}</AdminLayout>;
    return <div className="flex min-h-screen items-center justify-center bg-neutral-100 p-4">{content}</div>;
}
