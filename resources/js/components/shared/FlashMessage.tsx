import { useEffect, useState } from 'react';
import { usePage } from '@inertiajs/react';
import { PageProps } from '@/Types';

export default function FlashMessage() {
    const { flash } = usePage<PageProps>().props;
    const [visible, setVisible] = useState(true);

    useEffect(() => {
        setVisible(true);
        const id = setTimeout(() => setVisible(false), 4000);
        return () => clearTimeout(id);
    }, [flash.success, flash.error]);

    if (!visible || (!flash.success && !flash.error)) return null;

    return (
        <div className="fixed right-4 top-4 z-50 space-y-2">
            {flash.success && <div className="rounded bg-green-600 px-4 py-2 text-white">{flash.success}</div>}
            {flash.error && <div className="rounded bg-red-600 px-4 py-2 text-white">{flash.error}</div>}
        </div>
    );
}
