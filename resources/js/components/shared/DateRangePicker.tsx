import { useState } from 'react';

interface RangeValue {
    from: Date | null;
    to: Date | null;
}

interface Props {
    onChange: (value: RangeValue) => void;
}

export default function DateRangePicker({ onChange }: Props) {
    const [custom, setCustom] = useState(false);
    const [from, setFrom] = useState('');
    const [to, setTo] = useState('');

    const applyPreset = (preset: 'today' | 'last7' | 'month' | 'lastMonth') => {
        const now = new Date();
        if (preset === 'today') return onChange({ from: now, to: now });
        if (preset === 'last7') return onChange({ from: new Date(now.getTime() - 6 * 86400000), to: now });
        if (preset === 'month') return onChange({ from: new Date(now.getFullYear(), now.getMonth(), 1), to: now });
        return onChange({ from: new Date(now.getFullYear(), now.getMonth() - 1, 1), to: new Date(now.getFullYear(), now.getMonth(), 0) });
    };

    return (
        <div className="space-y-2">
            <div className="flex flex-wrap gap-2">
                <button type="button" className="rounded border px-2 py-1 text-sm" onClick={() => applyPreset('today')}>Today</button>
                <button type="button" className="rounded border px-2 py-1 text-sm" onClick={() => applyPreset('last7')}>Last 7 Days</button>
                <button type="button" className="rounded border px-2 py-1 text-sm" onClick={() => applyPreset('month')}>This Month</button>
                <button type="button" className="rounded border px-2 py-1 text-sm" onClick={() => applyPreset('lastMonth')}>Last Month</button>
                <button type="button" className="rounded border px-2 py-1 text-sm" onClick={() => setCustom((v) => !v)}>Custom</button>
            </div>
            {custom && (
                <div className="flex gap-2">
                    <input type="date" className="rounded border p-2 text-sm" value={from} onChange={(e) => setFrom(e.target.value)} />
                    <input type="date" className="rounded border p-2 text-sm" value={to} onChange={(e) => setTo(e.target.value)} />
                    <button type="button" className="rounded bg-neutral-900 px-3 py-2 text-sm text-white" onClick={() => onChange({ from: from ? new Date(from) : null, to: to ? new Date(to) : null })}>Apply</button>
                </div>
            )}
        </div>
    );
}
