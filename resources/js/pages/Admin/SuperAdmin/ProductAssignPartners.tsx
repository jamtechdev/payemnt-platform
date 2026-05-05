import AdminLayout from '@/layouts/AdminLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { router, usePage } from '@inertiajs/react';
import { PageProps } from '@/Types';
import { useState } from 'react';
import ReactSelect from 'react-select';
import { CheckCircle2, Trash2, ArrowLeft, Pencil, X, Check } from 'lucide-react';
import { Link } from '@inertiajs/react';

interface Currency { id: number; code: string; name: string; symbol: string | null; }
interface AssignedPartner { id: number; name: string; currency_id: number | null; base_price: string | null; guide_price: string | null; }
interface DisabledPartner { id: number; name: string; currency_id: number | null; base_price: string | null; guide_price: string | null; }

interface Row { id: number; name: string; currency_id: string; base_price: string; guide_price: string; }

interface Props {
    product: { id: number; name: string; description: string | null; image: string | null; status: string };
    allPartners: { id: number; name: string }[];
    assignedPartners: AssignedPartner[];
    disabledPartners: DisabledPartner[];
    currencies: Currency[];
}

export default function ProductAssignPartners({ product, allPartners, assignedPartners, disabledPartners, currencies }: Props) {
    const { flash } = usePage<PageProps>().props;
    const flashAny = flash as any;

    const currencyOptions = currencies.map((c) => ({
        value: String(c.id),
        label: `${c.code} — ${c.name}${c.symbol ? ` (${c.symbol})` : ''}`,
    }));

    const [rows, setRows] = useState<Row[]>(
        assignedPartners.map((p) => ({
            id: p.id, name: p.name,
            currency_id: p.currency_id ? String(p.currency_id) : '',
            base_price: p.base_price ?? '',
            guide_price: p.guide_price ?? '',
        }))
    );

    // Which row is being edited
    const [editingId, setEditingId] = useState<number | null>(null);
    const [editForm, setEditForm] = useState<{ currency_id: string; base_price: string; guide_price: string }>({ currency_id: '', base_price: '', guide_price: '' });

    // New partner add form
    const [selectedPartner, setSelectedPartner] = useState<{ value: number; label: string } | null>(null);
    const [addForm, setAddForm] = useState({ currency_id: '', base_price: '', guide_price: '' });

    const [saving, setSaving] = useState(false);
    const [removing, setRemoving] = useState<number | null>(null);

    const assignedIds = rows.map((r) => r.id);
    const availableOptions = allPartners
        .filter((p) => !assignedIds.includes(p.id))
        .map((p) => ({ value: p.id, label: p.name }));

    const handlePartnerSelect = (option: { value: number; label: string } | null) => {
        setSelectedPartner(option);
        if (option) {
            const prev = disabledPartners.find((d) => d.id === option.value);
            setAddForm({
                currency_id: prev?.currency_id ? String(prev.currency_id) : '',
                base_price: prev?.base_price ?? '',
                guide_price: prev?.guide_price ?? '',
            });
        } else {
            setAddForm({ currency_id: '', base_price: '', guide_price: '' });
        }
    };

    const handleAssign = () => {
        if (!selectedPartner || !addForm.currency_id || !addForm.base_price) return;
        setSaving(true);
        router.post(
            route('admin.products.sync-partners', product.id),
            { partners: [{ id: selectedPartner.value, currency_id: Number(addForm.currency_id), base_price: addForm.base_price, guide_price: addForm.guide_price || null }] },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setRows((prev) => [...prev, { id: selectedPartner.value, name: selectedPartner.label, currency_id: addForm.currency_id, base_price: addForm.base_price, guide_price: addForm.guide_price }]);
                    setSelectedPartner(null);
                    setAddForm({ currency_id: '', base_price: '', guide_price: '' });
                    setSaving(false);
                },
                onError: () => setSaving(false),
            }
        );
    };

    const startEdit = (row: Row) => {
        setEditingId(row.id);
        setEditForm({ currency_id: row.currency_id, base_price: row.base_price, guide_price: row.guide_price });
    };

    const cancelEdit = () => setEditingId(null);

    const saveEdit = (id: number) => {
        if (!editForm.currency_id || !editForm.base_price) return;
        setSaving(true);
        router.post(
            route('admin.products.sync-partners', product.id),
            { partners: [{ id, currency_id: Number(editForm.currency_id), base_price: editForm.base_price, guide_price: editForm.guide_price || null }] },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setRows((prev) => prev.map((r) => r.id === id ? { ...r, ...editForm } : r));
                    setEditingId(null);
                    setSaving(false);
                },
                onError: () => setSaving(false),
            }
        );
    };

    const handleRemove = (id: number) => {
        if (!confirm('Remove this partner? Their pricing will be saved and can be restored later.')) return;
        setRemoving(id);
        router.post(
            route('admin.products.remove-partner', product.id),
            { partner_id: id },
            {
                preserveScroll: true,
                onSuccess: () => { setRows((prev) => prev.filter((r) => r.id !== id)); setRemoving(null); },
                onError: () => setRemoving(null),
            }
        );
    };

    const isRestored = selectedPartner ? disabledPartners.some((d) => d.id === selectedPartner.value && d.currency_id) : false;

    return (
        <AdminLayout title="Assign Partners">
            <div className="space-y-5">

                <Link href={route('admin.products.index')} className="flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-800">
                    <ArrowLeft className="h-4 w-4" /> Back to Products
                </Link>

                {flashAny?.success && (
                    <div className="rounded-lg border border-green-200 bg-green-50 px-4 py-2 text-sm text-green-800">
                        ✅ {flashAny.success}
                    </div>
                )}

                {/* Product Card */}
                <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div className="flex items-center gap-4">
                        {product.image ? (
                            <img
                                src={product.image.startsWith('http') ? product.image : `/storage/${product.image}`}
                                alt={product.name}
                                className="h-16 w-16 rounded-xl border border-slate-200 object-cover shrink-0"
                            />
                        ) : (
                            <div className="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl border-2 border-dashed border-slate-200 bg-slate-50 text-xs text-slate-400">
                                No img
                            </div>
                        )}
                        <div className="min-w-0">
                            <div className="flex items-center gap-2">
                                <h2 className="truncate text-base font-semibold text-slate-800">{product.name}</h2>
                                <span className={`shrink-0 rounded-full px-2 py-0.5 text-xs font-medium ${
                                    product.status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'
                                }`}>{product.status}</span>
                            </div>
                            {product.description && (
                                <p className="mt-0.5 truncate text-sm text-slate-500">{product.description}</p>
                            )}
                            <p className="mt-1 text-xs text-slate-400">{rows.length} partner{rows.length !== 1 ? 's' : ''} assigned</p>
                        </div>
                    </div>
                </div>

                {/* Assigned Partners */}
                <div className="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                    <div className="border-b border-slate-100 bg-slate-50 px-5 py-3 flex items-center justify-between">
                        <p className="text-sm font-semibold text-slate-700">Assigned Partners</p>
                        <span className="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">{rows.length}</span>
                    </div>

                    {rows.length === 0 && (
                        <p className="px-5 py-8 text-center text-sm text-slate-400">No partners assigned yet.</p>
                    )}

                    <div className="divide-y divide-slate-100">
                        {rows.map((row) => {
                            const cur = currencies.find((c) => String(c.id) === row.currency_id);
                            const isEditing = editingId === row.id;

                            return (
                                <div key={row.id} className="px-5 py-4">
                                    {!isEditing ? (
                                        /* View mode */
                                        <div className="flex items-center justify-between">
                                            <div className="flex items-center gap-3">
                                                <CheckCircle2 className="h-5 w-5 shrink-0 text-emerald-500" />
                                                <div>
                                                    <p className="text-sm font-semibold text-slate-800">{row.name}</p>
                                                    <div className="mt-0.5 flex items-center gap-2 text-xs text-slate-500">
                                                        <span className="rounded bg-blue-50 px-1.5 py-0.5 font-mono font-semibold text-blue-700">{cur?.code ?? '—'}</span>
                                                        <span>Base: <strong className="text-slate-700">{cur?.symbol}{row.base_price}</strong></span>
                                                        <span>·</span>
                                                        <span>Guide: <strong className="text-emerald-700">{cur?.symbol}{row.guide_price || '—'}</strong></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div className="flex items-center gap-1.5">
                                                <button onClick={() => startEdit(row)} className="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700" title="Edit pricing">
                                                    <Pencil className="h-4 w-4" />
                                                </button>
                                                <button onClick={() => handleRemove(row.id)} disabled={removing === row.id} className="rounded-lg p-1.5 text-red-400 hover:bg-red-50 hover:text-red-600 disabled:opacity-40" title="Remove">
                                                    <Trash2 className="h-4 w-4" />
                                                </button>
                                            </div>
                                        </div>
                                    ) : (
                                        /* Edit mode */
                                        <div className="space-y-3 rounded-xl border border-emerald-200 bg-emerald-50/40 p-4">
                                            <p className="text-sm font-semibold text-slate-700">{row.name}</p>

                                            <div className="space-y-1.5">
                                                <Label>Currency</Label>
                                                <ReactSelect
                                                    options={currencyOptions}
                                                    value={currencyOptions.find((o) => o.value === editForm.currency_id) ?? null}
                                                    onChange={(val) => setEditForm({ ...editForm, currency_id: val?.value ?? '' })}
                                                    placeholder="Select currency..."
                                                    classNamePrefix="rs"
                                                    menuPortalTarget={document.body}
                                                    menuPosition="fixed"
                                                    styles={{
                                                        control: (base, state) => ({ ...base, borderRadius: '0.5rem', borderColor: state.isFocused ? '#10b981' : '#e2e8f0', minHeight: '40px', backgroundColor: 'white' }),
                                                        menuPortal: (base) => ({ ...base, zIndex: 9999 }),
                                                    }}
                                                />
                                            </div>

                                            <div className="grid grid-cols-2 gap-3">
                                                <div className="space-y-1.5">
                                                    <Label>Base Price</Label>
                                                    <Input type="number" min="0" step="0.01" placeholder="0.00" value={editForm.base_price} onChange={(e) => setEditForm({ ...editForm, base_price: e.target.value })} />
                                                </div>
                                                <div className="space-y-1.5">
                                                    <Label>Guide Price</Label>
                                                    <Input type="number" min="0" step="0.01" placeholder="0.00" value={editForm.guide_price} onChange={(e) => setEditForm({ ...editForm, guide_price: e.target.value })} />
                                                </div>
                                            </div>

                                            <div className="flex gap-2">
                                                <Button onClick={() => saveEdit(row.id)} disabled={saving || !editForm.currency_id || !editForm.base_price} size="sm" className="flex-1 bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-40">
                                                    <Check className="mr-1.5 h-3.5 w-3.5" /> {saving ? 'Saving...' : 'Save'}
                                                </Button>
                                                <Button onClick={cancelEdit} size="sm" variant="outline" className="flex-1">
                                                    <X className="mr-1.5 h-3.5 w-3.5" /> Cancel
                                                </Button>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            );
                        })}
                    </div>
                </div>

                {/* Add New Partner */}
                {availableOptions.length > 0 && (
                    <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
                        <p className="text-sm font-semibold text-slate-700">Assign New Partner</p>

                        <div className="space-y-1.5">
                            <Label>Select Partner</Label>
                            <ReactSelect
                                options={availableOptions}
                                value={selectedPartner}
                                onChange={(val) => handlePartnerSelect(val as { value: number; label: string } | null)}
                                placeholder="Search partner..."
                                isClearable
                                classNamePrefix="rs"
                                styles={{
                                    control: (base, state) => ({ ...base, borderRadius: '0.5rem', borderColor: state.isFocused ? '#10b981' : '#e2e8f0', boxShadow: state.isFocused ? '0 0 0 2px rgba(16,185,129,0.2)' : 'none', '&:hover': { borderColor: '#10b981' }, minHeight: '42px' }),
                                    option: (base, state) => ({ ...base, backgroundColor: state.isSelected ? '#10b981' : state.isFocused ? '#f0fdf4' : 'white', color: state.isSelected ? 'white' : '#1e293b', fontSize: '0.875rem' }),
                                    menu: (base) => ({ ...base, borderRadius: '0.5rem', zIndex: 40 }),
                                }}
                            />
                        </div>

                        {selectedPartner && (
                            <div className="space-y-3 rounded-xl border border-emerald-100 bg-emerald-50/40 p-4">
                                {isRestored && (
                                    <span className="inline-block rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-700">
                                        ↩ Previous pricing restored
                                    </span>
                                )}

                                <div className="space-y-1.5">
                                    <Label>Currency</Label>
                                    <ReactSelect
                                        options={currencyOptions}
                                        value={currencyOptions.find((o) => o.value === addForm.currency_id) ?? null}
                                        onChange={(val) => setAddForm({ ...addForm, currency_id: val?.value ?? '' })}
                                        placeholder="Select currency..."
                                        classNamePrefix="rs"
                                        menuPortalTarget={document.body}
                                        menuPosition="fixed"
                                        styles={{
                                            control: (base, state) => ({ ...base, borderRadius: '0.5rem', borderColor: state.isFocused ? '#10b981' : '#e2e8f0', minHeight: '40px', backgroundColor: 'white' }),
                                            menuPortal: (base) => ({ ...base, zIndex: 9999 }),
                                        }}
                                    />
                                </div>

                                <div className="grid grid-cols-2 gap-3">
                                    <div className="space-y-1.5">
                                        <Label>Base Price</Label>
                                        <Input type="number" min="0" step="0.01" placeholder="0.00" value={addForm.base_price} onChange={(e) => setAddForm({ ...addForm, base_price: e.target.value })} />
                                        <p className="text-xs text-slate-400">Internal cost</p>
                                    </div>
                                    <div className="space-y-1.5">
                                        <Label>Guide Price</Label>
                                        <Input type="number" min="0" step="0.01" placeholder="0.00" value={addForm.guide_price} onChange={(e) => setAddForm({ ...addForm, guide_price: e.target.value })} />
                                        <p className="text-xs text-slate-400">Partner sells at</p>
                                    </div>
                                </div>

                                <Button onClick={handleAssign} disabled={!addForm.currency_id || !addForm.base_price || saving} className="w-full bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-40">
                                    {saving ? 'Saving...' : `Assign ${selectedPartner.label}`}
                                </Button>
                            </div>
                        )}
                    </div>
                )}

            </div>
        </AdminLayout>
    );
}
