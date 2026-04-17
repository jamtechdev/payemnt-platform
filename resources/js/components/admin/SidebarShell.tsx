import FlashMessage from '@/components/shared/FlashMessage';
import { Sheet, SheetContent, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { PageProps } from '@/Types';
import { ReactNode, useEffect, useState } from 'react';
import { Toaster } from 'sonner';
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
    const [mobileNavOpen, setMobileNavOpen] = useState(false);

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
            <Toaster richColors position="top-right" closeButton />
            <div className="flex h-full">
                <AdminSidebar url={url} navItems={navItems} collapsed={collapsed} className="hidden" />

                <div className="flex h-full min-w-0 flex-1 flex-col">
                    <AdminHeader
                        title={title}
                        auth={auth}
                        sidebarCollapsed={collapsed}
                        onToggleSidebar={toggleSidebar}
                        onOpenMobileNav={() => setMobileNavOpen(true)}
                    />

                    <main className="flex-1 overflow-y-auto animate-in fade-in-0 duration-300 px-4 py-6 lg:px-8 dark:bg-gray-900">
                        <FlashMessage />
                        <div className="rounded-2xl border border-slate-200/70 bg-white/95 dark:border-slate-700/70 dark:bg-gray-800/95 p-4 shadow-[0_8px_30px_rgba(2,6,23,0.04)] lg:p-6">{children}</div>
                    </main>

                    <AdminFooter />
                </div>
            </div>

            <Sheet open={mobileNavOpen} onOpenChange={setMobileNavOpen}>
                <SheetContent side="left" className="w-[88vw] max-w-xs border-r-0 bg-transparent p-0 shadow-none">
                    <SheetHeader className="sr-only">
                        <SheetTitle>Navigation</SheetTitle>
                    </SheetHeader>
                    <AdminSidebar url={url} navItems={navItems} collapsed={false} className="flex w-full max-w-none border-r-0" onNavigate={() => setMobileNavOpen(false)} />
                </SheetContent>
            </Sheet>
        </div>
    );
}
