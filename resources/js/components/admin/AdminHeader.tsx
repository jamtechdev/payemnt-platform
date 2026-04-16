import ProfileMenu from '@/components/admin/ProfileMenu';
import { PageProps } from '@/Types';
import { Menu, PanelLeftClose, PanelLeftOpen } from 'lucide-react';

interface AdminHeaderProps {
    title: string;
    subtitle?: string;
    auth: PageProps['auth'];
    sidebarCollapsed: boolean;
    onToggleSidebar: () => void;
    onOpenMobileNav: () => void;
}

export default function AdminHeader({ title, subtitle, auth, sidebarCollapsed, onToggleSidebar, onOpenMobileNav }: AdminHeaderProps) {
    const roleLabel = auth.role ? auth.role.replace('_', ' ').replace(/\b\w/g, (char) => char.toUpperCase()) : 'Admin';

    return (
        <header className="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur">
            <div className="flex items-center justify-between px-4 py-3 lg:px-8">
                <div className="flex items-center gap-3">
                    <button
                        type="button"
                        onClick={onOpenMobileNav}
                        className="inline-flex rounded-lg border border-slate-200 p-2 text-slate-600 transition hover:bg-slate-100 hover:text-slate-900 lg:hidden"
                        aria-label="Open navigation menu"
                    >
                        <Menu className="h-4 w-4" />
                    </button>
                    <button
                        type="button"
                        onClick={onToggleSidebar}
                        className="hidden rounded-lg border border-slate-200 p-2 text-slate-600 transition hover:bg-slate-100 hover:text-slate-900 lg:inline-flex"
                        aria-label={sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'}
                    >
                        {sidebarCollapsed ? <PanelLeftOpen className="h-4 w-4" /> : <PanelLeftClose className="h-4 w-4" />}
                    </button>
                    <div>
                        <p className="text-xs font-medium uppercase tracking-wide text-[#0e9f84]">Welcome, {auth.user?.name ?? 'User'}</p>
                        <h1 className="text-2xl font-bold tracking-tight text-slate-900">{title}</h1>
                        {subtitle ? <p className="text-sm text-slate-500">{subtitle}</p> : null}
                    </div>
                </div>
                <div className="flex items-center gap-3">
                    <span className="hidden rounded-full border border-[#99e7d7] bg-[#e9fcf8] px-3 py-1 text-xs font-medium text-[#0e9f84] md:inline-flex">
                        {roleLabel}
                    </span>
                    <ProfileMenu auth={auth} />
                </div>
            </div>
        </header>
    );
}
