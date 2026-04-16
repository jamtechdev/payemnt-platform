import { useEffect, useState } from 'react';
import { usePage } from '@inertiajs/react';
import { PageProps } from '@/Types';
import { CheckCircle2, CircleAlert, X } from 'lucide-react';

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
        <div className="fixed right-4 top-4 z-50 flex w-[min(92vw,24rem)] flex-col gap-3">
            {flash.success && (
                <div className="flex items-start gap-3 rounded-2xl border border-emerald-200 bg-white px-4 py-3 text-emerald-900 shadow-lg shadow-emerald-100/60">
                    <CheckCircle2 className="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" />
                    <p className="flex-1 text-sm font-medium">{flash.success}</p>
                    <button type="button" onClick={() => setVisible(false)} className="text-emerald-500 transition hover:text-emerald-700" aria-label="Dismiss notification">
                        <X className="h-4 w-4" />
                    </button>
                </div>
            )}
            {flash.error && (
                <div className="flex items-start gap-3 rounded-2xl border border-rose-200 bg-white px-4 py-3 text-rose-900 shadow-lg shadow-rose-100/60">
                    <CircleAlert className="mt-0.5 h-5 w-5 shrink-0 text-rose-600" />
                    <p className="flex-1 text-sm font-medium">{flash.error}</p>
                    <button type="button" onClick={() => setVisible(false)} className="text-rose-500 transition hover:text-rose-700" aria-label="Dismiss notification">
                        <X className="h-4 w-4" />
                    </button>
                </div>
            )}
        </div>
    );
}
