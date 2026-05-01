<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\TransactionLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Payment::query()
            ->with(['customer:id,first_name,last_name,email', 'partner:id,name', 'product:id,name,product_code'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = $request->string('search')->toString();
                $q->where(function ($sub) use ($term) {
                    $sub->where('transaction_number', 'like', "%{$term}%")
                        ->orWhere('stripe_payment_intent', 'like', "%{$term}%")
                        ->orWhereHas('customer', fn ($c) => $c->where('email', 'like', "%{$term}%"));
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->toString()))
            ->latest('paid_at');

        $transactions = $query->paginate(10)->withQueryString();

        return Inertia::render('Admin/Transactions/TransactionList', [
            'transactions' => $transactions,
            'filters'      => $request->only(['search', 'status']),
        ]);
    }

    public function show(Payment $transaction): Response
    {
        $transaction->load([
            'customer:id,first_name,last_name,email,phone,status,customer_data',
            'partner:id,name',
            'product:id,name,product_code',
            'transactionLogs' => fn ($query) => $query->latest()->limit(30),
        ]);

        return Inertia::render('Admin/Transactions/TransactionDetail', [
            'transaction' => $transaction,
        ]);
    }

    public function updateCustomerDetails(Request $request, Payment $transaction): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $customer = $transaction->customer;
        $old = $customer?->only(['first_name', 'last_name', 'email', 'phone']) ?? [];
        $transaction->customer()->update($validated);
        AuditLog::record('transaction_customer_updated', $transaction, $old, $validated, $request->user());

        return back()->with('success', 'Customer details updated.');
    }

    public function suspendPolicy(Payment $transaction): RedirectResponse
    {
        $oldStatus = $transaction->status;
        $transaction->update(['status' => Payment::STATUS_SUSPENDED]);
        AuditLog::record('transaction_policy_suspended', $transaction, ['status' => $oldStatus], ['status' => Payment::STATUS_SUSPENDED], request()->user());

        return back()->with('success', 'Policy suspended successfully.');
    }

    public function addPolicyNote(Request $request, Payment $transaction): RedirectResponse
    {
        $validated = $request->validate([
            'note' => ['required', 'string', 'max:1000'],
        ]);

        $transaction->update(['notes' => $validated['note']]);
        AuditLog::record('transaction_policy_note_added', $transaction, [], ['note' => $validated['note']], $request->user());

        return back()->with('success', 'Policy note added.');
    }

    public function retryFailedRequest(Payment $transaction): RedirectResponse
    {
        if (! in_array($transaction->status, [Payment::STATUS_FAILED, Payment::STATUS_CANCELLED], true)) {
            return back()->with('error', 'Only failed or cancelled policies can be retried.');
        }

        $oldStatus = $transaction->status;
        $transaction->update(['status' => Payment::STATUS_PENDING]);
        TransactionLog::query()->create([
            'payment_id' => $transaction->id,
            'partner_id' => $transaction->partner_id,
            'event' => 'retry_requested_by_admin',
            'request_payload' => [],
            'response_payload' => ['status' => Payment::STATUS_PENDING],
            'status_code' => 202,
            'source' => 'admin',
            'occurred_at' => now(),
        ]);
        AuditLog::record('transaction_retry_requested', $transaction, ['status' => $oldStatus], ['status' => Payment::STATUS_PENDING], request()->user());

        return back()->with('success', 'Retry queued successfully.');
    }
}
