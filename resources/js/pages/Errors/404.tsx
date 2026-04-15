import { Link, usePage } from '@inertiajs/react';
import AdminLayout from '@/layouts/AdminLayout';
import { PageProps } from '@/Types';

export default function Error404() {
    const { auth } = usePage<PageProps>().props;
    const homeHref = auth.user ? route('admin.cs.dashboard') : route('login');
    const content = (
        <div className="mx-auto max-w-xl rounded bg-white p-8 text-center shadow">
            <div className="text-4xl">🔎</div>
            <h1 className="mt-3 text-xl font-semibold">Page not found</h1>
            <p className="mt-2 text-neutral-600">The page you are looking for does not exist.</p>
            <Link href={homeHref} className="mt-4 inline-block rounded bg-neutral-900 px-4 py-2 text-white">Go home</Link>
        </div>
    );
    if (auth.user) return <AdminLayout title="404">{content}</AdminLayout>;
    return <div className="flex min-h-screen items-center justify-center bg-neutral-100 p-4">{content}</div>;
}
