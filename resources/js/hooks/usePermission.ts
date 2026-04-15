import { usePage } from '@inertiajs/react';
import { PageProps } from '@/Types';

export function usePermission(permission: string): boolean {
    const { auth } = usePage<PageProps>().props;
    return auth.permissions.includes(permission);
}

export function useHasRole(role: string): boolean {
    const { auth } = usePage<PageProps>().props;
    return auth.role === role;
}
