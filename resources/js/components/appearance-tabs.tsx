import { cn } from '@/lib/utils';
import { Sun } from 'lucide-react';
import { HTMLAttributes } from 'react';

/** Theme tabs removed — application is light mode only. */
export default function AppearanceToggleTab({ className = '', ...props }: HTMLAttributes<HTMLDivElement>) {
    return (
        <div className={cn('inline-flex gap-1 rounded-lg border border-border bg-muted/50 p-1', className)} {...props}>
            <span className="flex items-center rounded-md bg-background px-3.5 py-1.5 text-sm text-foreground shadow-sm">
                <Sun className="-ml-1 h-4 w-4" />
                <span className="ml-1.5">Light</span>
            </span>
        </div>
    );
}
