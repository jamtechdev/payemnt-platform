import AdminLayout from '@/layouts/AdminLayout';

type LooseRecord = Record<string, unknown>;

function asRecord(input: unknown): LooseRecord {
    if (input && typeof input === 'object') return input as LooseRecord;
    return {};
}

// 🔥 DATE FORMATTER
function formatDate(value: unknown): string {
    if (!value) return '—';

    const date = new Date(value as string);

    if (isNaN(date.getTime())) return String(value);

    return new Intl.DateTimeFormat('en-GB', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(date);
}

export default function PartnerDetail({ partner }: { partner: unknown }) {
    const model = asRecord(partner);

    // 🔥 auto handled date fields
    const dateFields = ['created_at', 'updated_at', 'email_verified_at', 'last_login_at'];

    return (
        <AdminLayout title="Partner detail">
            <div className="mx-auto w-full max-w-4xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                {/* TITLE */}
                <h2 className="mb-6 text-lg font-semibold text-slate-800 dark:text-white">Partner information</h2>

                {/* CONTENT */}
                <div className="space-y-3">
                    {Object.entries(model).map(([key, value]) => (
                        <div
                            key={key}
                            className="flex items-center justify-between border-b border-slate-100 pb-2 last:border-none dark:border-slate-700"
                        >
                            {/* LABEL */}
                            <span className="text-sm font-medium text-slate-600 dark:text-slate-300">{key}</span>

                            {/* VALUE */}
                            <span className="max-w-[60%] truncate text-sm text-slate-800 dark:text-slate-100">
                                {dateFields.includes(key)
                                    ? formatDate(value)
                                    : typeof value === 'string'
                                      ? value
                                      : value === null
                                        ? '—'
                                        : JSON.stringify(value)}
                            </span>
                        </div>
                    ))}
                </div>
            </div>
        </AdminLayout>
    );
}
