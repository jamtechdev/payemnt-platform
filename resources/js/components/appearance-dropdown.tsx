import { Button } from '@/components/ui/button';
import { Sun } from 'lucide-react';
import { HTMLAttributes } from 'react';

/** Theme toggle removed — application is light mode only. */
export default function AppearanceToggleDropdown({ className = '', ...props }: HTMLAttributes<HTMLDivElement>) {
    return (
        <div className={className} {...props}>
            <Button variant="ghost" size="icon" className="h-9 w-9 rounded-md" type="button" disabled aria-label="Light mode">
                <Sun className="h-5 w-5 text-muted-foreground" />
            </Button>
        </div>
    );
}
