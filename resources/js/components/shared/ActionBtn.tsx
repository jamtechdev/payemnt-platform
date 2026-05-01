import { ReactNode } from 'react';
import { Link } from '@inertiajs/react';

type Tone = 'primary' | 'success' | 'danger' | 'warning' | 'muted';

const toneClass: Record<Tone, string> = {
    primary: 'text-primary bg-primary/8 hover:bg-primary/15 border-primary/20',
    success: 'text-emerald-600 bg-emerald-50 hover:bg-emerald-100 border-emerald-200',
    danger:  'text-red-600 bg-red-50 hover:bg-red-100 border-red-200',
    warning: 'text-amber-600 bg-amber-50 hover:bg-amber-100 border-amber-200',
    muted:   'text-slate-400 bg-slate-50 hover:bg-slate-100 border-slate-200',
};

interface BaseProps {
    tone?: Tone;
    title?: string;
    disabled?: boolean;
    children: ReactNode;
}

interface ButtonProps extends BaseProps {
    onClick: () => void;
    href?: never;
}

interface LinkProps extends BaseProps {
    href: string;
    onClick?: never;
}

type ActionBtnProps = ButtonProps | LinkProps;

const cls = (tone: Tone) =>
    `inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1.5 text-xs font-medium transition-colors ${toneClass[tone]}`;

export default function ActionBtn({ tone = 'primary', title, disabled, children, ...rest }: ActionBtnProps) {
    if ('href' in rest && rest.href) {
        return (
            <Link href={rest.href} className={cls(tone)} title={title}>
                {children}
            </Link>
        );
    }
    return (
        <button
            type="button"
            className={cls(tone) + (disabled ? ' cursor-not-allowed opacity-40' : '')}
            title={title}
            disabled={disabled}
            onClick={(rest as ButtonProps).onClick}
        >
            {children}
        </button>
    );
}
