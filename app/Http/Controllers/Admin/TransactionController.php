<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
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
        $transaction->load(['customer:id,first_name,last_name,email,phone', 'partner:id,name', 'product:id,name,product_code']);

        return Inertia::render('Admin/Transactions/TransactionDetail', [
            'transaction' => $transaction,
        ]);
    }
}
