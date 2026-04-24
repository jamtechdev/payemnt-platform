<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\CustomerFilterRequest;
use App\Jobs\GenerateCustomerExportJob;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(CustomerFilterRequest $request): Response
    {
        $canViewPaymentAmount = $request->user()?->can('customers.view_payment_amount') ?? false;

        $query = Customer::query()
            ->with(['partner', 'product', 'payments' => fn ($q) => $q->latest('paid_at')])
            ->when($request->filled('partner_id'), fn ($q) => $q->where('partner_id', $request->integer('partner_id')))
            ->when($request->filled('product_id'), fn ($q) => $q->where('product_id', $request->integer('product_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->toString()))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('customer_since', '>=', $request->string('date_from')->toString()))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('customer_since', '<=', $request->string('date_to')->toString()))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = $request->string('search')->toString();
                $q->where(function ($sub) use ($term) {
                    $sub->where('first_name', 'like', "%{$term}%")
                        ->orWhere('last_name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%")
                        ->orWhere('phone', 'like', "%{$term}%")
                        ->orWhere('uuid', 'like', "%{$term}%");
                });
            });

        $perPage = max(1, min((int) $request->integer('per_page', 5), 5));
        $customers = $query->paginate($perPage)
            ->withQueryString()
            ->through(function (Customer $customer) use ($canViewPaymentAmount): array {
                $latestPayment = $customer->payments->first();

                $payload = [
                    'uuid' => $customer->uuid,
                    'first_name' => $customer->first_name,
                    'last_name' => $customer->last_name,
                    'full_name' => $customer->full_name,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                    'status' => $customer->status,
                    'cover_end_date' => optional($customer->cover_end_date)->toDateString(),
                    'customer_since' => optional($customer->customer_since)->toDateString(),
                    'partner' => ['uuid' => $customer->partner?->uuid, 'name' => $customer->partner?->name],
                    'product' => ['uuid' => $customer->product?->uuid, 'name' => $customer->product?->name],
                    'profile_pic' => $customer->profile_pic,
                    'last_payment_date' => optional($latestPayment?->payment_date)->toDateTimeString(),
                ];

                if ($canViewPaymentAmount) {
                    $payload['latest_payment_amount'] = $latestPayment?->amount;
                }

                return $payload;
            });

        return Inertia::render('Admin/CustomerService/CustomerList', [
            'customers' => $customers,
            'filters' => $request->all(),
        ]);
    }

    public function show(string $uuid): Response
    {
        $canViewPaymentAmount = auth()->user()?->can('customers.view_payment_amount') ?? false;
        $customer = Customer::query()
            ->with(['partner', 'product.fields', 'payments'])
            ->where('uuid', $uuid)
            ->firstOrFail();

        $paymentHistory = $customer->payments->map(function ($payment) use ($canViewPaymentAmount): array {
            return [
                'uuid' => $payment->uuid,
                'payment_date' => optional($payment->payment_date)->toDateTimeString(),
                'currency' => $payment->currency,
                'transaction_reference' => $payment->transaction_reference,
                'payment_status' => $payment->payment_status,
                'amount' => $canViewPaymentAmount ? $payment->amount : null,
                'restricted' => ! $canViewPaymentAmount,
            ];
        })->values();

        return Inertia::render('Admin/CustomerService/CustomerDetail', [
            'customer' => $customer,
            'payment_history' => $paymentHistory,
            'can_view_payment_amount' => $canViewPaymentAmount,
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $term = $request->string('search')->toString();
        $customers = Customer::query()
            ->where(fn ($query) => $query
                ->where('first_name', 'like', "%{$term}%")
                ->orWhere('last_name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%"))
            ->limit(20)
            ->get();

        return response()->json($customers);
    }

    public function exportExpiring(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        // BRD open question 4: Download expiring customers per partner
        $partnerId = $request->integer('partner_id');

        $query = Customer::query()
            ->with(['partner:id,name', 'product:id,name'])
            ->expiringSoon();

        if ($partnerId) {
            $query->where('partner_id', $partnerId);
        }

        $customers = $query->orderBy('cover_end_date')->limit(10_000)->get();

        $filename = 'expiring-covers-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($customers): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Customer ID', 'Full Name', 'Email', 'Phone', 'Partner', 'Product', 'Cover End Date', 'Days Remaining']);
            foreach ($customers as $customer) {
                fputcsv($handle, [
                    $customer->uuid,
                    $customer->full_name,
                    $customer->email,
                    $customer->phone,
                    $customer->partner?->name,
                    $customer->product?->name,
                    optional($customer->cover_end_date)->toDateString(),
                    now()->diffInDays($customer->cover_end_date, false),
                ]);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function export(CustomerFilterRequest $request): JsonResponse
    {
        $jobId = (string) Str::uuid();
        Cache::put("export_job:{$jobId}", ['status' => 'queued'], now()->addHour());
        GenerateCustomerExportJob::dispatch($jobId, $request->validated());

        return response()->json(['job_id' => $jobId, 'status' => 'queued']);
    }

    public function downloadExport(string $jobId)
    {
        $state = Cache::get("export_job:{$jobId}");
        if (! $state) {
            return response()->json(['status' => 'not_found'], 404);
        }
        if (($state['status'] ?? null) !== 'completed') {
            return response()->json(['status' => $state['status'] ?? 'processing']);
        }

        return Storage::disk('local')->download($state['path']);
    }
}
