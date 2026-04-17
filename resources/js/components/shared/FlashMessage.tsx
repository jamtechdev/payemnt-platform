import { PageProps } from '@/Types';
import { usePage } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import { toast } from 'sonner';

export default function FlashMessage() {
    const { flash } = usePage<PageProps>().props;
    const lastKey = useRef<string | null>(null);

    useEffect(() => {
        const key = `${flash.success ?? ''}|${flash.error ?? ''}`;
        if (!flash.success && !flash.error) {
            return;
        }
        if (key === lastKey.current) {
            return;
        }
        lastKey.current = key;
        if (flash.success) {
            toast.success(String(flash.success), { id: 'inertia-flash-success' });
        }
        if (flash.error) {
            toast.error(String(flash.error), { id: 'inertia-flash-error' });
        }
    }, [flash.success, flash.error]);

    return null;
}
