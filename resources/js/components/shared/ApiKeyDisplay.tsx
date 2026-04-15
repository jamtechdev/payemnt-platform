import { useState } from 'react';

interface Props {
    maskedKey: string;
    regenerate: () => void;
    revealKey?: string;
}

export default function ApiKeyDisplay({ maskedKey, regenerate, revealKey }: Props) {
    const [copied, setCopied] = useState(false);
    const [revealed, setRevealed] = useState(false);

    const value = revealed && revealKey ? revealKey : maskedKey;

    const copy = async () => {
        await navigator.clipboard.writeText(value);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };

    const reveal = () => {
        setRevealed(true);
        setTimeout(() => setRevealed(false), 10000);
    };

    return (
        <div className="space-y-2">
            <div className="rounded border bg-neutral-50 p-2 font-mono text-sm">{value}</div>
            <div className="flex gap-2">
                <button className="rounded border px-2 py-1 text-sm" onClick={copy}>{copied ? 'Copied' : 'Copy'}</button>
                {revealKey && <button className="rounded border px-2 py-1 text-sm" onClick={reveal}>Reveal</button>}
                <button className="rounded bg-amber-600 px-2 py-1 text-sm text-white" onClick={regenerate}>Regenerate</button>
            </div>
        </div>
    );
}
