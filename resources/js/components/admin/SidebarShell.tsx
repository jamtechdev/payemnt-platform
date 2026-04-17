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
        <div className="h-screen overflow-hidden bg-gradient-to-br from-background via-background to-muted/30">
            <Toaster richColors position="top-right" closeButton />

            <div className="flex h-full">
                {/* DESKTOP SIDEBAR */}
                <div className="hidden h-full lg:block">
                    <AdminSidebar url={url} navItems={navItems} collapsed={collapsed} className="h-full overflow-y-auto" />
                </div>

                {/* MAIN AREA */}
                <div className="flex h-full min-w-0 flex-1 flex-col">
                    <AdminHeader
                        title={title}
                        auth={auth}
                        sidebarCollapsed={collapsed}
                        onToggleSidebar={toggleSidebar}
                        onOpenMobileNav={() => setMobileNavOpen(true)}
                    />

                    <main className="animate-in fade-in-0 flex-1 overflow-y-auto px-4 py-6 duration-300 lg:px-8 dark:bg-gray-900">
                        <FlashMessage />

                        <div className="rounded-2xl border border-slate-200/70 bg-white/95 p-4 shadow-[0_8px_30px_rgba(2,6,23,0.04)] lg:p-6 dark:border-slate-700/70 dark:bg-gray-800/95">
                            {children}
                        </div>
                    </main>

                    <AdminFooter />
                </div>
            </div>

            {/* MOBILE SIDEBAR */}
            <Sheet open={mobileNavOpen} onOpenChange={setMobileNavOpen}>
                <SheetContent side="left" className="h-full w-[88vw] max-w-xs overflow-hidden p-0">
                    <SheetHeader className="sr-only">
                        <SheetTitle>Navigation</SheetTitle>
                    </SheetHeader>

                    <div className="h-full overflow-y-auto">
                        <AdminSidebar url={url} navItems={navItems} collapsed={false} className="h-full" onNavigate={() => setMobileNavOpen(false)} />
                    </div>
                </SheetContent>
            </Sheet>
        </div>
    );
}
