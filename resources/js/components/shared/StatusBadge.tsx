type BadgeType = 'customer' | 'product' | 'partner' | 'user' | 'payment';

interface Props {
    status: string;
    type?: BadgeType;
}

const colorMap: Record<string, string> = {
    active: 'bg-green-100 text-green-700',
    success: 'bg-green-100 text-green-700',
    successful: 'bg-green-100 text-green-700',
    inactive: 'bg-neutral-100 text-neutral-700',
    cancelled: 'bg-neutral-100 text-neutral-700',
    expired: 'bg-red-100 text-red-700',
    failed: 'bg-red-100 text-red-700',
    pending: 'bg-amber-100 text-amber-700',
    refunded: 'bg-blue-100 text-blue-700',
    deleted: 'bg-red-100 text-red-700',
};

export default function StatusBadge({ status }: Props) {
    const cls = colorMap[status?.toLowerCase()] ?? 'bg-neutral-100 text-neutral-700';
    return <span className={`rounded-full px-2 py-1 text-xs font-medium ${cls}`}>{status}</span>;
}
