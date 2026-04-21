<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Customer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class GenerateCustomerExportJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;
    public int $tries = 3;

    public function __construct(public string $jobId, public array $filters = []) {}

    public function handle(): void
    {
        Cache::put("export_job:{$this->jobId}", ['status' => 'processing'], now()->addHour());

        $query = Customer::query()->with(['partner', 'product']);

        if (! empty($this->filters['partner_id'])) {
            $query->where('partner_id', (int) $this->filters['partner_id']);
        }
        if (! empty($this->filters['product_id'])) {
            $query->where('product_id', (int) $this->filters['product_id']);
        }
        if (! empty($this->filters['status'])) {
            $query->where('status', (string) $this->filters['status']);
        }
        if (! empty($this->filters['date_from'])) {
            $query->whereDate('customer_since', '>=', (string) $this->filters['date_from']);
        }
        if (! empty($this->filters['date_to'])) {
            $query->whereDate('customer_since', '<=', (string) $this->filters['date_to']);
        }
        if (! empty($this->filters['search'])) {
            $term = (string) $this->filters['search'];
            $query->where(function ($sub) use ($term): void {
                $sub->where('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%")
                    ->orWhere('uuid', 'like', "%{$term}%");
            });
        }

        $rows = $query->orderBy('id')->limit(10_000)->get();

        $csv = "uuid,full_name,email,partner,product,status,cover_end_date\n";
        foreach ($rows as $row) {
            $csv .= sprintf(
                "\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\"\n",
                $row->uuid,
                $row->full_name,
                $row->email,
                $row->partner?->name,
                $row->product?->name,
                $row->status,
                $row->cover_end_date?->toDateString()
            );
        }

        $path = "exports/customers-{$this->jobId}.csv";
        Storage::disk('local')->put($path, $csv);
        Cache::put("export_job:{$this->jobId}", ['status' => 'completed', 'path' => $path], now()->addHour());
    }
}
