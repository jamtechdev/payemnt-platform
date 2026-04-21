<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Partner;

class PartnerRepository
{
    public function findByCodeOrFail(string $partnerCode): Partner
    {
        return Partner::query()->active()->where('partner_code', $partnerCode)->firstOrFail();
    }
}
