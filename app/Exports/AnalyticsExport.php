<?php

declare(strict_types=1);

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;

class AnalyticsExport implements FromCollection
{
    public function __construct(private readonly array $report)
    {
    }

    public function collection(): Collection
    {
        return collect([
            ['metric' => 'customers_per_partner', 'payload' => json_encode($this->report['customers_per_partner'] ?? [])],
            ['metric' => 'customers_per_product', 'payload' => json_encode($this->report['customers_per_product'] ?? [])],
            ['metric' => 'revenue_per_product', 'payload' => json_encode($this->report['revenue_per_product'] ?? [])],
            ['metric' => 'daily_revenue', 'payload' => json_encode($this->report['daily_revenue'] ?? [])],
            ['metric' => 'monthly_revenue', 'payload' => json_encode($this->report['monthly_revenue'] ?? [])],
        ]);
    }
}
