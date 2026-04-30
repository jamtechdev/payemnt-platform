import SidebarShell, { NavItem } from '@/components/admin/SidebarShell';
import { PageProps } from '@/Types';
import { usePage } from '@inertiajs/react';
import { ReactNode, useMemo } from 'react';

interface Props {
    title: string;
    children: ReactNode;
}

function buildNavItems(auth: PageProps['auth']): NavItem[] {
    const items: NavItem[] = [];
    const { modules, permissions, role } = auth;

    if (modules.includes('dashboard')) {
        if (role === 'super_admin') {
            items.push({ href: route('admin.platform.dashboard'), label: 'Dashboard' });
        } else if (role === 'reconciliation_admin') {
            items.push({ href: route('admin.reports.dashboard'), label: 'Dashboard' });
        } else {
            items.push({ href: route('admin.cs.dashboard'), label: 'Dashboard' });
        }
    }

    if (role === 'super_admin' || permissions.includes('transactions.view')) {
        items.push({ href: route('admin.transactions.index'), label: 'Transactions' });
    }

    if (modules.includes('products') && (permissions.includes('products.view') || permissions.includes('products.manage'))) {
        items.push({ href: route('admin.products.index'), label: 'Products' });
    }

    if (modules.includes('partners') && (permissions.includes('partners.view') || permissions.includes('partners.manage'))) {
        items.push({ href: route('admin.partners.index'), label: 'Partners' });
    }

    if (modules.includes('reports')) {
        if (permissions.includes('reports.customer_acquisition')) {
            items.push({ href: route('admin.reports.customer-acquisition'), label: 'Acquisition' });
        }
        if (permissions.includes('reports.revenue_by_product')) {
            items.push({ href: route('admin.reports.revenue'), label: 'Revenue' });
        }
        if (permissions.includes('reports.partner_performance')) {
            items.push({ href: route('admin.partners.performance'), label: 'Partner performance' });
        }
    }

    if (role === 'super_admin') {
        items.push({ href: route('admin.api-docs.index'), label: 'API Guide' });
    }

    return items;
}

export default function AdminLayout({ title, children }: Props) {
    const { auth, url } = usePage<PageProps & { url: string }>().props;
    const navItems = useMemo(() => buildNavItems(auth), [auth]);

    return (
        <SidebarShell auth={auth} url={url} title={title} navItems={navItems}>
            {children}
        </SidebarShell>
    );
}
