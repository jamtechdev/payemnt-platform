import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { PageProps } from '@/Types';
import { Link, router } from '@inertiajs/react';
import { ChevronDown, LogOut, Settings, User } from 'lucide-react';

interface ProfileMenuProps {
    auth: PageProps['auth'];
}

function initials(name: string): string {
    return (
        name
            .split(/\s+/)
            .filter(Boolean)
            .slice(0, 2)
            .map((part) => part[0]?.toUpperCase() ?? '')
            .join('') || '?'
    );
}

export default function ProfileMenu({ auth }: ProfileMenuProps) {
    if (!auth.user) return null;

    return (
        <DropdownMenu>
            <DropdownMenuTrigger className="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-2 py-1 text-sm outline-none transition hover:border-emerald-300">
                <Avatar className="h-8 w-8 border border-slate-200">
                    {auth.user.avatar_url ? <AvatarImage src={auth.user.avatar_url} alt="" /> : null}
                    <AvatarFallback className="bg-emerald-600 text-xs text-white">{initials(auth.user.name)}</AvatarFallback>
                </Avatar>
                <span className="hidden max-w-28 truncate text-slate-700 sm:block">{auth.user.name}</span>
                <ChevronDown className="h-4 w-4 text-slate-500" />
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-56">
                <DropdownMenuLabel>
                    <p className="truncate font-medium">{auth.user.name}</p>
                    <p className="truncate text-xs font-normal text-slate-500">{auth.user.email}</p>
                </DropdownMenuLabel>
                <DropdownMenuSeparator />
                <DropdownMenuItem asChild>
                    <Link href={route('admin.profile.index')} className="cursor-pointer">
                        <User className="mr-2 h-4 w-4" /> My profile
                    </Link>
                </DropdownMenuItem>
                <DropdownMenuItem asChild>
                    <Link href={route('admin.settings.index')} className="cursor-pointer">
                        <Settings className="mr-2 h-4 w-4" /> Settings
                    </Link>
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem
                    className="cursor-pointer text-red-600 focus:text-red-700"
                    onClick={() => router.post(route('logout'))}
                >
                    <LogOut className="mr-2 h-4 w-4" /> Log out
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
