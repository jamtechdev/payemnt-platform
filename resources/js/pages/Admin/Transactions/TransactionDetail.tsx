import AdminLayout from '@/layouts/AdminLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import StatusBadge from '@/components/shared/StatusBadge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Link, router } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

import { ReactNode, useMemo, useState } from 'react';

type LooseRecord = Record<string, unknown>;

function fmt(value: unknown): string {
    if (value === null || value === undefined || value === '') return '—';
    return String(value);
}

function fmtDate(value: unknown): string {
    if (!value) return '—';
    const d = new Date(String(value));
    if (isNaN(d.getTime())) return String(value);
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function fmtDateTime(value: unknown): string {
    if (!value) return '—';
    const d = new Date(String(value));
    if (isNaN(d.getTime())) return String(value);
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function hasValue(value: unknown): boolean {
    return value !== null && value !== undefined && value !== '' && value !== '—';
}

function InfoRow({ label, value }: { label: string; value: unknown | ReactNode }) {
    if (!hasValue(value)) return null;
    return (
        <div className="flex items-center justify-between border-b border-border/60 py-2 last:border-none">
            <span className="text-sm font-medium text-muted-foreground">{label}</span>
            <span className="max-w-[60%] text-sm text-foreground">
                {typeof value === 'string' || typeof value === 'number' ? value : (value as ReactNode)}
            </span>
        </div>
    );
}

export default function TransactionDetail({ transaction }: { transaction: unknown }) {
    const t = (transaction && typeof transaction === 'object' ? transaction : {}) as LooseRecord;
    const customer = (t.customer && typeof t.customer === 'object' ? t.customer : {}) as LooseRecord;
    const partner  = (t.partner  && typeof t.partner  === 'object' ? t.partner  : {}) as LooseRecord;
    const product  = (t.product  && typeof t.product  === 'object' ? t.product  : {}) as LooseRecord;
    const transactionLogs = Array.isArray(t.transaction_logs) ? (t.transaction_logs as LooseRecord[]) : [];

    const [firstName, setFirstName] = useState(String(customer.first_name ?? ''));
    const [lastName, setLastName] = useState(String(customer.last_name ?? ''));
    const [email, setEmail] = useState(String(customer.email ?? ''));
    const [phone, setPhone] = useState(String(customer.phone ?? ''));
    const [note, setNote] = useState('');

    const policyNotes = useMemo(() => {
        const customerData = (customer.customer_data && typeof customer.customer_data === 'object')
            ? (customer.customer_data as LooseRecord)
            : {};
        return Array.isArray(customerData.policy_notes) ? (customerData.policy_notes as LooseRecord[]) : [];
    }, [customer.customer_data]);

    const submitCustomerUpdate = () => {
        router.patch(route('admin.transactions.customer.update', t.id), {
            first_name: firstName,
            last_name: lastName,
            email,
            phone,
        });
    };

    const suspendPolicy = () => {
        if (!confirm('Suspend policy for this customer?')) return;
        router.post(route('admin.transactions.policy.suspend', t.id));
    };

    const submitPolicyNote = () => {
        if (!note.trim()) return;
        router.post(route('admin.transactions.policy.notes.store', t.id), { note });
        setNote('');
    };

    const retryRequest = () => {
        if (!confirm('Retry this failed/cancelled request?')) return;
        router.post(route('admin.transactions.retry', t.id));
    };

    return (
        <AdminLayout title="Transaction Detail">
            <div className="mb-4">
                <Link href={route('admin.transactions.index')} className="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
                    <ArrowLeft className="h-4 w-4" /> Back to Transactions
                </Link>
            </div>

            <div className="grid gap-4 lg:grid-cols-2">
                <Card>
                    <CardHeader><CardTitle className="text-base">Transaction Info</CardTitle></CardHeader>
                    <CardContent>
                        <InfoRow label="Transaction #"       value={t.transaction_number} />
                        <InfoRow label="Status"              value={<StatusBadge status={String(t.status ?? '')} type="customer" />} />
                        <InfoRow label="Payment Message"     value={t.payment_message} />
                        <InfoRow label="Cover Duration"      value={t.cover_duration} />
                        <InfoRow label="Cover Start Date"    value={fmtDate(t.cover_start_date)} />
                        <InfoRow label="Cover End Date"      value={fmtDate(t.cover_end_date)} />
                        <InfoRow label="Date Added"          value={fmtDateTime(t.paid_at)} />
                    </CardContent>
                </Card>

                {/* <Card>
                    <CardHeader><CardTitle className="text-base">Payment Info</CardTitle></CardHeader>
                    <CardContent>
                        <InfoRow label="Stripe Payment Intent" value={t.stripe_payment_intent} />
                        <InfoRow label="Stripe Payment Status" value={t.stripe_payment_status} />
                        <InfoRow label="Transaction Reference" value={t.transaction_reference} />
                        <InfoRow label="Amount"                value={t.amount} />
                        <InfoRow label="Currency"              value={t.currency} />
                    </CardContent>
                </Card> */}

                <Card>
                    <CardHeader><CardTitle className="text-base">Customer Info</CardTitle></CardHeader>
                    <CardContent>
                        <InfoRow label="Name"  value={`${customer.first_name ?? ''} ${customer.last_name ?? ''}`.trim()} />
                        <InfoRow label="Email" value={customer.email} />
                        <InfoRow label="Phone" value={customer.phone} />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle className="text-base">Partner & Product</CardTitle></CardHeader>
                    <CardContent>
                        <InfoRow label="Partner"      value={partner.name} />
                        <InfoRow label="Product"      value={product.name} />
                        <InfoRow label="Product Code" value={product.product_code} />
                        <InfoRow label="Product Type" value={t.product_type} />
                    </CardContent>
                </Card>

                {/* Action Functions - commented out: data comes from swap, not editable
                <Card className="lg:col-span-2">
                    <CardHeader><CardTitle className="text-base">Action Functions</CardTitle></CardHeader>
                    <CardContent className="space-y-6">
                        <div className="grid gap-3 md:grid-cols-2">
                            <div className="space-y-2">
                                <Label>First name</Label>
                                <Input value={firstName} onChange={(e) => setFirstName(e.target.value)} />
                            </div>
                            <div className="space-y-2">
                                <Label>Last name</Label>
                                <Input value={lastName} onChange={(e) => setLastName(e.target.value)} />
                            </div>
                            <div className="space-y-2">
                                <Label>Email</Label>
                                <Input type="email" value={email} onChange={(e) => setEmail(e.target.value)} />
                            </div>
                            <div className="space-y-2">
                                <Label>Phone</Label>
                                <Input value={phone} onChange={(e) => setPhone(e.target.value)} />
                            </div>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Button type="button" onClick={submitCustomerUpdate}>Edit customer details</Button>
                            <Button type="button" variant="destructive" onClick={suspendPolicy}>Suspend policy</Button>
                            <Button type="button" variant="outline" onClick={retryRequest}>Retry failed request</Button>
                        </div>

                        <div className="space-y-2 border-t border-border pt-4">
                            <Label>Add note to policy</Label>
                            <textarea
                                className="min-h-24 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                placeholder="Add a policy note..."
                                value={note}
                                onChange={(e) => setNote(e.target.value)}
                            />
                            <Button type="button" variant="outline" onClick={submitPolicyNote}>Add note</Button>
                        </div>

                        {policyNotes.length > 0 && (
                            <div className="space-y-2">
                                <p className="text-sm font-medium text-muted-foreground">Policy notes</p>
                                <div className="space-y-2">
                                    {policyNotes.map((item, idx) => (
                                        <div key={idx} className="rounded-md border border-border p-3 text-sm">
                                            <p>{String(item.note ?? '')}</p>
                                            <p className="mt-1 text-xs text-muted-foreground">
                                                {String(item.added_by ?? 'admin')} - {fmtDateTime(item.added_at)}
                                            </p>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}

                        {transactionLogs.length > 0 && (
                            <div className="space-y-2 border-t border-border pt-4">
                                <p className="text-sm font-medium text-muted-foreground">API history</p>
                                <div className="space-y-2">
                                    {transactionLogs.map((log) => (
                                        <div key={String(log.id)} className="rounded-md border border-border p-3 text-xs">
                                            <p className="font-medium">{String(log.event ?? 'event')}</p>
                                            <p className="text-muted-foreground">{fmtDateTime(log.occurred_at)}</p>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
                */}
            </div>
        </AdminLayout>
    );
}
