import { Separator } from '@/components/ui/separator';
import { Link } from '@inertiajs/react';
import {
    BarChart3,
    Bolt,
    BriefcaseBusiness,
    ChevronRight,
    FileText,
    LayoutDashboard,
    Settings,
    Shield,
    ShoppingBag,
    Users,
    UserSquare2,
} from 'lucide-react';
import { ReactNode } from 'react';
import { cn } from '@/lib/utils';
import { NavItem } from './SidebarShell';

interface AdminSidebarProps {
    url: string;
    navItems: NavItem[];
    collapsed: boolean;
    className?: string;
    onNavigate?: () => void;
}

const iconMap: Record<string, ReactNode> = {
    Dashboard: <LayoutDashboard className="h-4 w-4" />,
    Customers: <Users className="h-4 w-4" />,
    Products: <ShoppingBag className="h-4 w-4" />,
    Partners: <BriefcaseBusiness className="h-4 w-4" />,
    Users: <UserSquare2 className="h-4 w-4" />,
    Acquisition: <BarChart3 className="h-4 w-4" />,
    Revenue: <BarChart3 className="h-4 w-4" />,
    'Partner performance': <BarChart3 className="h-4 w-4" />,
    'Audit logs': <Shield className="h-4 w-4" />,
    Settings: <Settings className="h-4 w-4" />,
    'My profile': <FileText className="h-4 w-4" />,
};

const sectionMap: Record<string, string> = {
    Dashboard: 'Overview',
    Customers: 'Operations',
    Products: 'Operations',
    Partners: 'Operations',
    Users: 'Management',
    Acquisition: 'Reports',
    Revenue: 'Reports',
    'Partner performance': 'Reports',
    'Audit logs': 'Governance',
    Settings: 'Governance',
    'My profile': 'Account',
};

export default function AdminSidebar({ url, navItems, collapsed, className, onNavigate }: AdminSidebarProps) {
    const currentPath = typeof url === 'string' && url.length > 0 ? url : window.location.pathname;

    const isItemActive = (href: string): boolean => {
        const itemPath = new URL(href, window.location.origin).pathname;
        if (currentPath === itemPath) return true;
        if (itemPath !== '/admin' && currentPath.startsWith(`${itemPath}/`)) return true;
        return false;
    };

    const groupedItems = navItems.reduce<Record<string, NavItem[]>>((groups, item) => {
        const section = sectionMap[item.label] ?? 'General';
        if (!groups[section]) {
            groups[section] = [];
        }
        groups[section].push(item);
        return groups;
    }, {});

    return (
        <aside
            className={cn(
                'h-screen shrink-0 border-r border-sidebar-border bg-sidebar text-sidebar-foreground transition-all duration-300 lg:flex lg:flex-col',
                collapsed ? 'w-20' : 'w-72',
                className,
            )}
        >
            <div className="px-4 py-5">
                <Link href={navItems[0]?.href ?? route('login')} className="block rounded-xl border border-sidebar-border/80 bg-sidebar-accent/40 px-3 py-3 shadow-sm">
                    {collapsed ? (
                        <span className="text-lg font-bold text-sidebar-primary">PS</span>
                    ) : (
                        <div className="space-y-1">
                            <div className="flex items-center gap-2">
                                <span className="inline-flex h-7 w-7 items-center justify-center rounded-md bg-sidebar-primary/20 text-sidebar-primary">
                                    <Bolt className="h-4 w-4" />
                                </span>
                                <p className="text-base font-semibold tracking-tight text-sidebar-foreground">
                                    Partner<span className="text-sidebar-primary">Sales</span>
                                </p>
                            </div>
                            <p className="text-[11px] uppercase tracking-wide text-sidebar-foreground/60">Admin command center</p>
                        </div>
                    )}
                </Link>

            <Separator className="bg-sidebar-border/80" />
            <nav className="sidebar-panal flex-1 space-y-5 overflow-auto px-3 py-4">
                {Object.entries(groupedItems).map(([section, items]) => (
                    <div key={section} className="space-y-1.5">
                        {!collapsed && <p className="px-2 text-[11px] font-semibold uppercase tracking-wider text-sidebar-foreground/70">{section}</p>}
                        {items.map((item, index) => {
                            const active = isItemActive(item.href);
                            return (
                                <Link
                                    key={item.href}
                                    href={item.href}
                                    onClick={onNavigate}
                                    className={`group flex items-center gap-2 rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200 ${
                                        active
                                            ? 'bg-sidebar-primary/14 text-sidebar-primary ring-1 ring-sidebar-primary/30'
                                            : 'text-sidebar-foreground/85 hover:bg-sidebar-accent/80 hover:text-sidebar-accent-foreground'
                                    }`}
                                    style={{ animationDelay: `${index * 35}ms` }}
                                    title={collapsed ? item.label : undefined}
                                >
                                    <span
                                        className={`inline-flex h-7 w-7 items-center justify-center rounded-lg transition ${
                                            active ? 'bg-sidebar-primary/18 text-sidebar-primary' : 'bg-sidebar-accent/90 text-sidebar-foreground/75 group-hover:bg-sidebar-accent'
                                        }`}
                                    >
                                        {iconMap[item.label] ?? <FileText className="h-4 w-4" />}
                                    </span>
                                    {!collapsed && (
                                        <>
                                            <span className="flex-1 truncate">{item.label}</span>
                                            <ChevronRight className={`h-3.5 w-3.5 transition ${active ? 'opacity-100' : 'opacity-0 group-hover:opacity-70'}`} />
                                        </>
                                    )}
                                </Link>
                            );
                        })}
                    </div>
                ))}
            </nav>
            <Separator className="bg-sidebar-border/80" />
            </div>
        </aside>
    );
}
