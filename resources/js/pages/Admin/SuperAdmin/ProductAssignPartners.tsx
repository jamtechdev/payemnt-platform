import AdminLayout from '@/layouts/AdminLayout';
import { Button } from '@/components/ui/button';
import { router, usePage } from '@inertiajs/react';
import { PageProps } from '@/Types';
import { useState } from 'react';
import ReactSelect from 'react-select';

interface PartnerOption {
    value: number;
    label: string;
}

interface Props {
    product: { id: number; name: string };
    allPartners: { id: number; name: string }[];
    assignedPartners: { id: number; name: string }[];
}

export default function ProductAssignPartners({ product, allPartners, assignedPartners }: Props) {
    const { flash } = usePage<PageProps>().props;
    const flashAny = flash as any;
    const options: PartnerOption[] = allPartners.map((p) => ({ value: p.id, label: p.name }));
    const defaultSelected: PartnerOption[] = assignedPartners.map((p) => ({ value: p.id, label: p.name }));

    const [selected, setSelected] = useState<PartnerOption[]>(defaultSelected);
    const [processing, setProcessing] = useState(false);

    const handleSave = () => {
        setProcessing(true);
        router.post(
            route('admin.products.sync-partners', product.id),
            { partner_ids: selected.map((s) => s.value) },
            {
                preserveScroll: true,
                onFinish: () => setProcessing(false),
            }
        );
    };

    return (
        <AdminLayout title="Assign Partners">
            <div className="mx-auto max-w-2xl space-y-6">
                <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                    <h2 className="text-lg font-semibold text-slate-800 dark:text-white">{product.name}</h2>
                    <p className="mt-1 text-sm text-slate-500">Select which partners can access this product.</p>

                    {flashAny?.success && (
                        <div className="mt-3 rounded-lg border border-green-200 bg-green-50 px-4 py-2 text-sm text-green-800">
                            ✅ {flashAny.success}
                        </div>
                    )}

                    <div className="mt-5">
                        <label className="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">
                            Partners
                        </label>
                        <ReactSelect
                            isMulti
                            options={options}
                            value={selected}
                            onChange={(val) => setSelected(val as PartnerOption[])}
                            placeholder="Search and select partners..."
                            classNamePrefix="rs"
                            styles={{
                                control: (base, state) => ({
                                    ...base,
                                    borderRadius: '0.5rem',
                                    borderColor: state.isFocused ? '#10b981' : '#e2e8f0',
                                    boxShadow: state.isFocused ? '0 0 0 2px rgba(16,185,129,0.2)' : 'none',
                                    '&:hover': { borderColor: '#10b981' },
                                    minHeight: '42px',
                                }),
                                multiValue: (base) => ({
                                    ...base,
                                    backgroundColor: '#eff6ff',
                                    borderRadius: '9999px',
                                    border: '1px solid #bfdbfe',
                                }),
                                multiValueLabel: (base) => ({
                                    ...base,
                                    color: '#1d4ed8',
                                    fontSize: '0.75rem',
                                    fontWeight: 500,
                                    paddingLeft: '8px',
                                }),
                                multiValueRemove: (base) => ({
                                    ...base,
                                    color: '#1d4ed8',
                                    borderRadius: '0 9999px 9999px 0',
                                    '&:hover': { backgroundColor: '#dbeafe', color: '#1e40af' },
                                }),
                                option: (base, state) => ({
                                    ...base,
                                    backgroundColor: state.isSelected ? '#10b981' : state.isFocused ? '#f0fdf4' : 'white',
                                    color: state.isSelected ? 'white' : '#1e293b',
                                    fontSize: '0.875rem',
                                }),
                                menu: (base) => ({ ...base, borderRadius: '0.5rem', boxShadow: '0 4px 16px rgba(0,0,0,0.1)' }),
                            }}
                        />
                        {selected.length > 0 && (
                            <p className="mt-2 text-xs text-slate-500">
                                {selected.length} partner{selected.length > 1 ? 's' : ''} selected
                            </p>
                        )}
                    </div>

                    <div className="mt-6 flex items-center gap-3">
                        <Button
                            onClick={handleSave}
                            disabled={processing}
                            className="bg-emerald-600 text-white hover:bg-emerald-700"
                        >
                            {processing ? 'Saving...' : 'Save Partners'}
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => window.history.back()}
                        >
                            Cancel
                        </Button>
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}
