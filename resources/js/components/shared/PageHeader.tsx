import { ReactNode } from 'react';
import { Link } from '@inertiajs/react';

interface Crumb {
    label: string;
    href?: string;
}

interface Props {
    title: string;
    breadcrumbs?: Crumb[];
    actions?: ReactNode;
}

export default function PageHeader({ title, breadcrumbs = [], actions }: Props) {
    return (
        <div className="mb-4 flex items-start justify-between gap-3">
            <div>
                <h2 className="text-xl font-semibold">{title}</h2>
                {breadcrumbs.length > 0 && (
                    <div className="mt-1 flex items-center gap-2 text-sm text-neutral-500">
                        {breadcrumbs.map((crumb, i) => (
                            <span key={`${crumb.label}-${i}`} className="flex items-center gap-2">
                                {crumb.href ? <Link href={crumb.href}>{crumb.label}</Link> : <span>{crumb.label}</span>}
                                {i < breadcrumbs.length - 1 && <span>/</span>}
                            </span>
                        ))}
                    </div>
                )}
            </div>
            {actions && <div>{actions}</div>}
        </div>
    );
}
