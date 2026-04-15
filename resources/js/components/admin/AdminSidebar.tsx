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
import { NavItem } from './SidebarShell';

interface AdminSidebarProps {
    url: string;
    navItems: NavItem[];
    collapsed: boolean;
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

export default function AdminSidebar({ url, navItems, collapsed }: AdminSidebarProps) {
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
            className={`hidden h-screen shrink-0 border-r border-slate-200/70 bg-gradient-to-b from-slate-950 via-slate-900 to-slate-900 text-slate-100 transition-all duration-300 lg:flex lg:flex-col ${
                collapsed ? 'w-20' : 'w-72'
            }`}
        >
            <div className="px-4 py-5">
                <Link href={navItems[0]?.href ?? route('login')} className="block rounded-xl border border-white/10 bg-white/5 px-3 py-3 shadow-sm">
                    {collapsed ? (
                        <span className="text-lg font-bold text-[#7bf2da]">PS</span>
                    ) : (
                        <div className="space-y-1">
                            <div className="flex items-center gap-2">
                                <span className="inline-flex h-7 w-7 items-center justify-center rounded-md bg-[#0e9f84]/25 text-[#7bf2da]">
                                    <Bolt className="h-4 w-4" />
                                </span>
                                <p className="text-base font-semibold tracking-tight text-white">
                                    Partner<span className="text-[#7bf2da]">Sales</span>
                                </p>
                            </div>
                            <p className="text-[11px] uppercase tracking-wide text-slate-300">Admin command center</p>
                        </div>
                    )}
                </Link>
            </div>
            <Separator className="bg-white/10" />
            <nav className="flex-1 space-y-5 overflow-hidden px-3 py-4">
                {Object.entries(groupedItems).map(([section, items]) => (
                    <div key={section} className="space-y-1.5">
                        {!collapsed && <p className="px-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400">{section}</p>}
                        {items.map((item, index) => {
                            const active = isItemActive(item.href);
                            return (
                                <Link
                                    key={item.href}
                                    href={item.href}
                                    className={`group flex items-center gap-2 rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200 ${
                                        active
                                            ? 'bg-[#0e9f84]/25 text-[#b8ffef] ring-1 ring-[#0e9f84]/40'
                                            : 'text-slate-300 hover:bg-white/10 hover:text-white'
                                    }`}
                                    style={{ animationDelay: `${index * 35}ms` }}
                                    title={collapsed ? item.label : undefined}
                                >
                                    <span
                                        className={`inline-flex h-7 w-7 items-center justify-center rounded-lg transition ${
                                            active ? 'bg-[#0e9f84]/20 text-[#b8ffef]' : 'bg-white/5 text-slate-300 group-hover:bg-white/10'
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
            <Separator className="bg-white/10" />
        </aside>
    );
}
