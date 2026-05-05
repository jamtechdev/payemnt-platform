import AdminLayout from '@/layouts/AdminLayout';
import { usePage, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { PageProps } from '@/Types';
import { Pencil, Trash2, Plus, X, Check } from 'lucide-react';
import { useState } from 'react';

interface Currency {
    id: number;
    code: string;
    name: string;
    symbol: string | null;
    is_active: boolean;
}

interface Props {
    currencies: Currency[];
}

const CURRENCY_MAP: Record<string, { name: string; symbol: string }> = {
    AED: { name: 'UAE Dirham', symbol: 'د.إ' }, AFN: { name: 'Afghan Afghani', symbol: '؋' },
    ALL: { name: 'Albanian Lek', symbol: 'L' }, AMD: { name: 'Armenian Dram', symbol: '֏' },
    AOA: { name: 'Angolan Kwanza', symbol: 'Kz' }, ARS: { name: 'Argentine Peso', symbol: '$' },
    AUD: { name: 'Australian Dollar', symbol: 'A$' }, AZN: { name: 'Azerbaijani Manat', symbol: '₼' },
    BAM: { name: 'Bosnia-Herzegovina Mark', symbol: 'KM' }, BBD: { name: 'Barbadian Dollar', symbol: 'Bds$' },
    BDT: { name: 'Bangladeshi Taka', symbol: '৳' }, BGN: { name: 'Bulgarian Lev', symbol: 'лв' },
    BHD: { name: 'Bahraini Dinar', symbol: 'BD' }, BIF: { name: 'Burundian Franc', symbol: 'Fr' },
    BND: { name: 'Brunei Dollar', symbol: 'B$' }, BOB: { name: 'Bolivian Boliviano', symbol: 'Bs.' },
    BRL: { name: 'Brazilian Real', symbol: 'R$' }, BTN: { name: 'Bhutanese Ngultrum', symbol: 'Nu' },
    BWP: { name: 'Botswanan Pula', symbol: 'P' }, BYN: { name: 'Belarusian Ruble', symbol: 'Br' },
    BZD: { name: 'Belize Dollar', symbol: 'BZ$' }, CAD: { name: 'Canadian Dollar', symbol: 'C$' },
    CHF: { name: 'Swiss Franc', symbol: 'Fr' }, CLP: { name: 'Chilean Peso', symbol: '$' },
    CNY: { name: 'Chinese Yuan', symbol: '¥' }, COP: { name: 'Colombian Peso', symbol: '$' },
    CRC: { name: 'Costa Rican Colón', symbol: '₡' }, CZK: { name: 'Czech Koruna', symbol: 'Kč' },
    DKK: { name: 'Danish Krone', symbol: 'kr' }, DOP: { name: 'Dominican Peso', symbol: 'RD$' },
    DZD: { name: 'Algerian Dinar', symbol: 'دج' }, EGP: { name: 'Egyptian Pound', symbol: 'E£' },
    ETB: { name: 'Ethiopian Birr', symbol: 'Br' }, EUR: { name: 'Euro', symbol: '€' },
    GBP: { name: 'British Pound', symbol: '£' }, GEL: { name: 'Georgian Lari', symbol: '₾' },
    GHS: { name: 'Ghanaian Cedi', symbol: '₵' }, GMD: { name: 'Gambian Dalasi', symbol: 'D' },
    GTQ: { name: 'Guatemalan Quetzal', symbol: 'Q' }, HKD: { name: 'Hong Kong Dollar', symbol: 'HK$' },
    HNL: { name: 'Honduran Lempira', symbol: 'L' }, HUF: { name: 'Hungarian Forint', symbol: 'Ft' },
    IDR: { name: 'Indonesian Rupiah', symbol: 'Rp' }, ILS: { name: 'Israeli New Shekel', symbol: '₪' },
    INR: { name: 'Indian Rupee', symbol: '₹' }, IQD: { name: 'Iraqi Dinar', symbol: 'ع.د' },
    ISK: { name: 'Icelandic Króna', symbol: 'kr' }, JMD: { name: 'Jamaican Dollar', symbol: 'J$' },
    JOD: { name: 'Jordanian Dinar', symbol: 'JD' }, JPY: { name: 'Japanese Yen', symbol: '¥' },
    KES: { name: 'Kenyan Shilling', symbol: 'KSh' }, KHR: { name: 'Cambodian Riel', symbol: '៛' },
    KRW: { name: 'South Korean Won', symbol: '₩' }, KWD: { name: 'Kuwaiti Dinar', symbol: 'KD' },
    KZT: { name: 'Kazakhstani Tenge', symbol: '₸' }, LAK: { name: 'Laotian Kip', symbol: '₭' },
    LBP: { name: 'Lebanese Pound', symbol: 'L£' }, LKR: { name: 'Sri Lankan Rupee', symbol: 'Rs' },
    LRD: { name: 'Liberian Dollar', symbol: 'L$' }, LYD: { name: 'Libyan Dinar', symbol: 'LD' },
    MAD: { name: 'Moroccan Dirham', symbol: 'MAD' }, MDL: { name: 'Moldovan Leu', symbol: 'L' },
    MGA: { name: 'Malagasy Ariary', symbol: 'Ar' }, MMK: { name: 'Myanmar Kyat', symbol: 'K' },
    MNT: { name: 'Mongolian Tögrög', symbol: '₮' }, MUR: { name: 'Mauritian Rupee', symbol: 'Rs' },
    MWK: { name: 'Malawian Kwacha', symbol: 'MK' }, MXN: { name: 'Mexican Peso', symbol: 'MX$' },
    MYR: { name: 'Malaysian Ringgit', symbol: 'RM' }, MZN: { name: 'Mozambican Metical', symbol: 'MT' },
    NAD: { name: 'Namibian Dollar', symbol: 'N$' }, NGN: { name: 'Nigerian Naira', symbol: '₦' },
    NOK: { name: 'Norwegian Krone', symbol: 'kr' }, NPR: { name: 'Nepalese Rupee', symbol: 'Rs' },
    NZD: { name: 'New Zealand Dollar', symbol: 'NZ$' }, OMR: { name: 'Omani Rial', symbol: 'ر.ع.' },
    PEN: { name: 'Peruvian Sol', symbol: 'S/.' }, PHP: { name: 'Philippine Peso', symbol: '₱' },
    PKR: { name: 'Pakistani Rupee', symbol: 'Rs' }, PLN: { name: 'Polish Złoty', symbol: 'zł' },
    QAR: { name: 'Qatari Riyal', symbol: 'QR' }, RON: { name: 'Romanian Leu', symbol: 'lei' },
    RSD: { name: 'Serbian Dinar', symbol: 'din' }, RUB: { name: 'Russian Ruble', symbol: '₽' },
    RWF: { name: 'Rwandan Franc', symbol: 'Fr' }, SAR: { name: 'Saudi Riyal', symbol: 'SR' },
    SEK: { name: 'Swedish Krona', symbol: 'kr' }, SGD: { name: 'Singapore Dollar', symbol: 'S$' },
    SOS: { name: 'Somali Shilling', symbol: 'Sh' }, SYP: { name: 'Syrian Pound', symbol: 'S£' },
    THB: { name: 'Thai Baht', symbol: '฿' }, TND: { name: 'Tunisian Dinar', symbol: 'DT' },
    TRY: { name: 'Turkish Lira', symbol: '₺' }, TTD: { name: 'Trinidad Dollar', symbol: 'TT$' },
    TWD: { name: 'New Taiwan Dollar', symbol: 'NT$' }, TZS: { name: 'Tanzanian Shilling', symbol: 'Sh' },
    UAH: { name: 'Ukrainian Hryvnia', symbol: '₴' }, UGX: { name: 'Ugandan Shilling', symbol: 'Sh' },
    USD: { name: 'US Dollar', symbol: '$' }, UYU: { name: 'Uruguayan Peso', symbol: '$U' },
    UZS: { name: 'Uzbekistani Som', symbol: "so'm" }, VND: { name: 'Vietnamese Đồng', symbol: '₫' },
    XAF: { name: 'Central African CFA Franc', symbol: 'Fr' }, XOF: { name: 'West African CFA Franc', symbol: 'Fr' },
    YER: { name: 'Yemeni Rial', symbol: '﷼' }, ZAR: { name: 'South African Rand', symbol: 'R' },
    ZMW: { name: 'Zambian Kwacha', symbol: 'ZK' }, ZWL: { name: 'Zimbabwean Dollar', symbol: 'Z$' },
};

const emptyForm = { code: '', name: '', symbol: '', is_active: true };

export default function CurrencyList({ currencies }: Props) {
    const { flash } = usePage<PageProps>().props;
    const flashAny = flash as any;

    const [modal, setModal] = useState<{ open: boolean; mode: 'add' | 'edit'; id?: number }>({ open: false, mode: 'add' });
    const [form, setForm] = useState(emptyForm);
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});

    const openAdd = () => {
        setForm(emptyForm);
        setErrors({});
        setModal({ open: true, mode: 'add' });
    };

    const openEdit = (c: Currency) => {
        setForm({ code: c.code, name: c.name, symbol: c.symbol ?? '', is_active: c.is_active });
        setErrors({});
        setModal({ open: true, mode: 'edit', id: c.id });
    };

    const closeModal = () => setModal({ open: false, mode: 'add' });

    const handleCodeChange = (code: string) => {
        const upper = code.toUpperCase();
        const match = CURRENCY_MAP[upper];
        setForm((prev) => ({
            ...prev,
            code: upper,
            name: match?.name ?? prev.name,
            symbol: match?.symbol ?? prev.symbol,
        }));
    };

    const handleSubmit = () => {
        setProcessing(true);
        if (modal.mode === 'add') {
            router.post(route('admin.currencies.store'), form, {
                preserveScroll: true,
                onSuccess: () => { closeModal(); setProcessing(false); },
                onError: (e) => { setErrors(e); setProcessing(false); },
            });
        } else {
            router.patch(route('admin.currencies.update', modal.id), form, {
                preserveScroll: true,
                onSuccess: () => { closeModal(); setProcessing(false); },
                onError: (e) => { setErrors(e); setProcessing(false); },
            });
        }
    };

    const handleDelete = (id: number) => {
        if (!confirm('Remove this currency?')) return;
        router.delete(route('admin.currencies.destroy', id), { preserveScroll: true });
    };

    return (
        <AdminLayout title="Currencies">
            <div className="space-y-4">

                {flashAny?.success && (
                    <div className="rounded-lg border border-green-200 bg-green-50 px-4 py-2 text-sm text-green-800">
                        ✅ {flashAny.success}
                    </div>
                )}

                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-base font-semibold text-slate-800">Currencies</h2>
                        <p className="text-sm text-slate-500">{currencies.length} currencies configured</p>
                    </div>
                    <Button onClick={openAdd} className="gap-1.5 bg-emerald-600 text-white hover:bg-emerald-700">
                        <Plus className="h-4 w-4" /> Add Currency
                    </Button>
                </div>

                {/* Table */}
                <div className="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b border-slate-100 bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <th className="px-5 py-3">Code</th>
                                <th className="px-5 py-3">Name</th>
                                <th className="px-5 py-3">Symbol</th>
                                <th className="px-5 py-3">Status</th>
                                <th className="px-5 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-50">
                            {currencies.map((c) => (
                                <tr key={c.id} className="hover:bg-slate-50/60 transition-colors">
                                    <td className="px-5 py-3.5 font-mono font-semibold text-slate-800">{c.code}</td>
                                    <td className="px-5 py-3.5 text-slate-700">{c.name}</td>
                                    <td className="px-5 py-3.5 font-semibold text-slate-700">{c.symbol ?? '—'}</td>
                                    <td className="px-5 py-3.5">
                                        <span className={`rounded-full px-2.5 py-0.5 text-xs font-medium ${
                                            c.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'
                                        }`}>
                                            {c.is_active ? 'Active' : 'Inactive'}
                                        </span>
                                    </td>
                                    <td className="px-5 py-3.5 text-right">
                                        <div className="flex items-center justify-end gap-2">
                                            <button
                                                onClick={() => openEdit(c)}
                                                className="rounded-lg border border-slate-200 p-1.5 text-slate-400 hover:border-slate-300 hover:text-slate-700 transition-colors"
                                                title="Edit"
                                            >
                                                <Pencil className="h-3.5 w-3.5" />
                                            </button>
                                            <button
                                                onClick={() => handleDelete(c.id)}
                                                className="rounded-lg border border-red-100 p-1.5 text-red-400 hover:border-red-200 hover:text-red-600 transition-colors"
                                                title="Delete"
                                            >
                                                <Trash2 className="h-3.5 w-3.5" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                            {currencies.length === 0 && (
                                <tr>
                                    <td colSpan={5} className="px-5 py-12 text-center text-slate-400">
                                        No currencies yet. Click "Add Currency" to get started.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>

            {/* Modal */}
            {modal.open && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
                    <div className="w-full max-w-md rounded-2xl border border-slate-200 bg-white shadow-2xl">

                        {/* Modal Header */}
                        <div className="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                            <h3 className="text-base font-semibold text-slate-800">
                                {modal.mode === 'add' ? 'Add Currency' : 'Edit Currency'}
                            </h3>
                            <button onClick={closeModal} className="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                                <X className="h-4 w-4" />
                            </button>
                        </div>

                        {/* Modal Body */}
                        <div className="space-y-4 px-6 py-5">

                            {/* Code */}
                            <div className="space-y-1.5">
                                <Label>Currency Code</Label>
                                <Input
                                    placeholder="e.g. USD, EUR, NGN"
                                    value={form.code}
                                    onChange={(e) => modal.mode === 'add' ? handleCodeChange(e.target.value) : setForm({ ...form, code: e.target.value.toUpperCase() })}
                                    className="font-mono uppercase"
                                    maxLength={10}
                                    disabled={modal.mode === 'edit'}
                                />
                                {modal.mode === 'add' && form.code && CURRENCY_MAP[form.code] && (
                                    <p className="text-xs text-emerald-600">✓ Recognized currency — name & symbol auto-filled</p>
                                )}
                                {errors.code && <p className="text-xs text-red-500">{errors.code}</p>}
                            </div>

                            {/* Name */}
                            <div className="space-y-1.5">
                                <Label>Name</Label>
                                <Input
                                    placeholder="e.g. US Dollar"
                                    value={form.name}
                                    onChange={(e) => setForm({ ...form, name: e.target.value })}
                                />
                                {errors.name && <p className="text-xs text-red-500">{errors.name}</p>}
                            </div>

                            {/* Symbol */}
                            <div className="space-y-1.5">
                                <Label>Symbol</Label>
                                <Input
                                    placeholder="e.g. $, £, ₦"
                                    value={form.symbol}
                                    onChange={(e) => setForm({ ...form, symbol: e.target.value })}
                                    maxLength={10}
                                />
                            </div>

                            {/* Status */}
                            <div className="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50 px-4 py-3">
                                <div>
                                    <p className="text-sm font-medium text-slate-700">Active</p>
                                    <p className="text-xs text-slate-400">Partners can use this currency</p>
                                </div>
                                <button
                                    type="button"
                                    onClick={() => setForm({ ...form, is_active: !form.is_active })}
                                    className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors ${
                                        form.is_active ? 'bg-emerald-500' : 'bg-slate-300'
                                    }`}
                                >
                                    <span className={`inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform ${
                                        form.is_active ? 'translate-x-6' : 'translate-x-1'
                                    }`} />
                                </button>
                            </div>
                        </div>

                        {/* Modal Footer */}
                        <div className="flex items-center justify-end gap-2 border-t border-slate-100 px-6 py-4">
                            <Button variant="outline" onClick={closeModal}>Cancel</Button>
                            <Button
                                onClick={handleSubmit}
                                disabled={processing}
                                className="gap-1.5 bg-emerald-600 text-white hover:bg-emerald-700"
                            >
                                <Check className="h-4 w-4" />
                                {processing ? 'Saving...' : modal.mode === 'add' ? 'Add Currency' : 'Save Changes'}
                            </Button>
                        </div>
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}
