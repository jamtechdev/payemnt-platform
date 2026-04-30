<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\Admin\UpdateTransactionRequest;
use App\Http\Resources\Api\V1\PartnerTransactionResource;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminTransactionController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $transactions = Payment::query()
            ->with(['partner:id,name,partner_code', 'product:id,name,product_code'])
            ->when($request->filled('partner_id'), fn ($q) => $q->where('partner_id', $request->integer('partner_id')))
            ->when($request->filled('product_id'), fn ($q) => $q->where('product_id', $request->integer('product_id')))
            ->latest()
            ->paginate(min((int) $request->integer('per_page', 20), 100));

        return $this->paginated($transactions);
    }

    public function update(UpdateTransactionRequest $request, Payment $transaction): JsonResponse
    {
        $validated = $request->validated();

        $transaction->update($validated);

        return $this->success(new PartnerTransactionResource($transaction->fresh()));
    }

    public function suspend(Payment $transaction): JsonResponse
    {
        $transaction->update(['status' => Payment::STATUS_SUSPENDED]);

        return $this->success(new PartnerTransactionResource($transaction->fresh()));
    }

    public function addNote(Request $request, Payment $transaction): JsonResponse
    {
        $validated = $request->validate([
            'notes' => ['required', 'string', 'max:1000'],
        ]);

        $transaction->update(['notes' => $validated['notes']]);

        return $this->success(new PartnerTransactionResource($transaction->fresh()));
    }
}
