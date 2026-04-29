import { Separator } from '@/components/ui/separator';
import { Link } from '@inertiajs/react';
import {
    BarChart3,
    Bolt,
    BriefcaseBusiness,
    ChevronDown,
    ChevronRight,
    FileText,
    FolderOpen,
    HelpCircle,
    LayoutDashboard,
    Layers,
    ClipboardList,
    Newspaper,
    Settings,
    Shield,
    ShoppingBag,
    Users,
    UserSquare2,
} from 'lucide-react';
import { ReactNode, useState } from 'react';
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
    'Connect Categories': <FolderOpen className="h-4 w-4" />,
    'Connect Articles': <Newspaper className="h-4 w-4" />,
    FAQs: <HelpCircle className="h-4 w-4" />,
    'Rate APIs': <BarChart3 className="h-4 w-4" />,
    'App Resources': <Layers className="h-4 w-4" />,
    'Data Records':   <ClipboardList className="h-4 w-4" />,
    'Transactions':   <ClipboardList className="h-4 w-4" />,
    'Product Transactions': <FileText className="h-4 w-4" />,
    'Fund Wallets':   <FileText className="h-4 w-4" />,
    'Products Purchases': <FileText className="h-4 w-4" />,
    'Task Types': <FileText className="h-4 w-4" />,
    'Occupations': <FileText className="h-4 w-4" />,
    'Relationships': <FileText className="h-4 w-4" />,
};

const sectionMap: Record<string, string> = {
    Dashboard: 'Overview',
    Customers: 'Operations',
    Transactions: 'Operations',
    'Swap Offers': 'Operations',
    Products: 'Operations',
    Partners: 'Operations',
    Users: 'Management',
    Acquisition: 'Reports',
    Revenue: 'Reports',
    'Partner performance': 'Reports',
    'Audit logs': 'Governance',
    Settings: 'Governance',
    'My profile': 'Account',
    'Connect Categories': 'Connect',
    'Connect Articles': 'Connect',
    FAQs: 'Connect',
    'Rate APIs': 'Connect',
    'App Resources': 'Resources',
    'Data Records':   'Resources',
    'Transactions':   'Resources',
};

export default function AdminSidebar({ url, navItems, collapsed, className, onNavigate }: AdminSidebarProps) {
    const currentPath = typeof url === 'string' && url.length > 0 ? url : window.location.pathname;

    const isItemActive = (href?: string): boolean => {
        if (!href) return false;
        const itemPath = new URL(href, window.location.origin).pathname;
        if (currentPath === itemPath) return true;
        if (itemPath !== '/admin' && currentPath.startsWith(`${itemPath}/`)) return true;
        return false;
    };

    const isGroupActive = (item: NavItem): boolean => {
        if (item.children) return item.children.some((c) => c.children ? c.children.some((s) => isItemActive(s.href)) : isItemActive(c.href));
        return isItemActive(item.href);
    };

    const [openGroups, setOpenGroups] = useState<Record<string, boolean>>(() => {
        const init: Record<string, boolean> = {};
        navItems.forEach((item) => {
            if (item.children) {
                const anyChildActive = item.children.some((c) =>
                    c.children ? c.children.some((s) => isItemActive(s.href)) : isItemActive(c.href)
                );
                if (anyChildActive) {
                    init[item.label] = true;
                    item.children.forEach((c) => {
                        if (c.children && c.children.some((s) => isItemActive(s.href))) {
                            init[c.label] = true;
                        }
                    });
                }
            }
        });
        return init;
    });

    const toggleGroup = (label: string) => {
        setOpenGroups((prev) => ({ ...prev, [label]: !prev[label] }));
    };

    const groupedItems = navItems.reduce<Record<string, NavItem[]>>((groups, item) => {
        const section = sectionMap[item.label] ?? 'General';
        if (!groups[section]) groups[section] = [];
        groups[section].push(item);
        return groups;
    }, {});

    const renderItem = (item: NavItem, index: number) => {
        if (item.children) {
            const open = openGroups[item.label] ?? false;
            const active = isGroupActive(item);
            return (
                <div key={item.label}>
                    <button
                        onClick={() => toggleGroup(item.label)}
                        className={`group flex w-full items-center gap-2 rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200 ${
                            active
                                ? 'bg-sidebar-primary/14 text-sidebar-primary ring-1 ring-sidebar-primary/30'
                                : 'text-sidebar-foreground/85 hover:bg-sidebar-accent/80 hover:text-sidebar-accent-foreground'
                        }`}
                    >
                        <span className={`inline-flex h-7 w-7 items-center justify-center rounded-lg transition ${
                            active ? 'bg-sidebar-primary/18 text-sidebar-primary' : 'bg-sidebar-accent/90 text-sidebar-foreground/75'
                        }`}>
                            {iconMap[item.label] ?? <FileText className="h-4 w-4" />}
                        </span>
                        {!collapsed && (
                            <>
                                <span className="flex-1 truncate text-left">{item.label}</span>
                                {open ? <ChevronDown className="h-3.5 w-3.5" /> : <ChevronRight className="h-3.5 w-3.5" />}
                            </>
                        )}
                    </button>
                    {open && !collapsed && (
                        <div className="ml-4 mt-1 space-y-1 border-l border-sidebar-border/60 pl-3">
                            {item.children.map((child) => {
                                if (child.children) {
                                    const subOpen = openGroups[child.label] ?? false;
                                    const subActive = child.children.some((c) => isItemActive(c.href));
                                    return (
                                        <div key={child.label}>
                                            <button
                                                onClick={() => toggleGroup(child.label)}
                                                className={`flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm transition-all ${
                                                    subActive
                                                        ? 'font-medium text-sidebar-primary'
                                                        : 'text-sidebar-foreground/75 hover:bg-sidebar-accent/80 hover:text-sidebar-accent-foreground'
                                                }`}
                                            >
                                                <span className="h-1.5 w-1.5 rounded-full bg-current opacity-60" />
                                                <span className="flex-1 truncate text-left">{child.label}</span>
                                                {subOpen ? <ChevronDown className="h-3 w-3" /> : <ChevronRight className="h-3 w-3" />}
                                            </button>
                                            {subOpen && (
                                                <div className="ml-4 mt-1 space-y-1 border-l border-sidebar-border/40 pl-3">
                                                    {child.children.map((sub) => {
                                                        const subChildActive = isItemActive(sub.href);
                                                        return (
                                                            <Link
                                                                key={sub.href}
                                                                href={sub.href!}
                                                                onClick={onNavigate}
                                                                className={`flex items-center gap-2 rounded-lg px-3 py-1.5 text-xs transition-all ${
                                                                    subChildActive
                                                                        ? 'font-medium text-sidebar-primary'
                                                                        : 'text-sidebar-foreground/65 hover:bg-sidebar-accent/80 hover:text-sidebar-accent-foreground'
                                                                }`}
                                                            >
                                                                <span className="h-1 w-1 rounded-full bg-current opacity-50" />
                                                                {sub.label}
                                                            </Link>
                                                        );
                                                    })}
                                                </div>
                                            )}
                                        </div>
                                    );
                                }
                                const childActive = isItemActive(child.href);
                                return (
                                    <Link
                                        key={child.href}
                                        href={child.href!}
                                        onClick={onNavigate}
                                        className={`flex items-center gap-2 rounded-lg px-3 py-2 text-sm transition-all ${
                                            childActive
                                                ? 'bg-sidebar-primary/14 font-medium text-sidebar-primary'
                                                : 'text-sidebar-foreground/75 hover:bg-sidebar-accent/80 hover:text-sidebar-accent-foreground'
                                        }`}
                                    >
                                        <span className="h-1.5 w-1.5 rounded-full bg-current opacity-60" />
                                        {child.label}
                                    </Link>
                                );
                            })}
                        </div>
                    )}
                </div>
            );
        }

        const active = isItemActive(item.href);
        return (
            <Link
                key={item.href}
                href={item.href!}
                onClick={onNavigate}
                className={`group flex items-center gap-2 rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200 ${
                    active
                        ? 'bg-sidebar-primary/14 text-sidebar-primary ring-1 ring-sidebar-primary/30'
                        : 'text-sidebar-foreground/85 hover:bg-sidebar-accent/80 hover:text-sidebar-accent-foreground'
                }`}
                style={{ animationDelay: `${index * 35}ms` }}
                title={collapsed ? item.label : undefined}
            >
                <span className={`inline-flex h-7 w-7 items-center justify-center rounded-lg transition ${
                    active ? 'bg-sidebar-primary/18 text-sidebar-primary' : 'bg-sidebar-accent/90 text-sidebar-foreground/75 group-hover:bg-sidebar-accent'
                }`}>
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
    };

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
                        {items.map((item, index) => renderItem(item, index))}
                    </div>
                ))}
            </nav>
            <Separator className="bg-sidebar-border/80" />
            </div>
        </aside>
    );
}
