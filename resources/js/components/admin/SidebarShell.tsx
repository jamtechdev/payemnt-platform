import FlashMessage from '@/components/shared/FlashMessage';
import { PageProps } from '@/Types';
import { ReactNode, useEffect, useState } from 'react';
import AdminFooter from './AdminFooter';
import AdminHeader from './AdminHeader';
import AdminSidebar from './AdminSidebar';

export interface NavItem {
    href: string;
    label: string;
}

interface SidebarShellProps {
    auth: PageProps['auth'];
    url: string;
    title: string;
    navItems: NavItem[];
    children: ReactNode;
}

export default function SidebarShell({ auth, url, title, navItems, children }: SidebarShellProps) {
    const [collapsed, setCollapsed] = useState(false);

    useEffect(() => {
        const saved = localStorage.getItem('admin.sidebar.collapsed');
        if (saved === '1') setCollapsed(true);
    }, []);

    const toggleSidebar = () => {
        setCollapsed((prev) => {
            const next = !prev;
            localStorage.setItem('admin.sidebar.collapsed', next ? '1' : '0');
            return next;
        });
    };

    return (
        <div className="h-screen overflow-hidden bg-gradient-to-br from-slate-50 via-[#f2fffb] to-slate-100">
            <div className="flex h-full">
                <AdminSidebar url={url} navItems={navItems} collapsed={collapsed} />

                <div className="flex h-full min-w-0 flex-1 flex-col">
                    <AdminHeader title={title} auth={auth} sidebarCollapsed={collapsed} onToggleSidebar={toggleSidebar} />

                    <main className="flex-1 overflow-y-auto animate-in fade-in-0 duration-300 px-4 py-6 lg:px-8 dark:bg-gray-900">
                        <FlashMessage />
                        <div className="rounded-2xl border border-slate-200/70 bg-white/95 dark:border-slate-700/70 dark:bg-gray-800/95 p-4 shadow-[0_8px_30px_rgba(2,6,23,0.04)] lg:p-6">{children}</div>
                    </main>

                    <AdminFooter />
                </div>
            </div>
        </div>
    );
}
