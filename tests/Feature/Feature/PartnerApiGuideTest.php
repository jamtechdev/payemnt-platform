<?php

declare(strict_types=1);

namespace Tests\Feature\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerApiGuideTest extends TestCase
{
    use RefreshDatabase;

    public function test_partner_api_guide_endpoint_returns_contract(): void
    {
        $this->getJson('/api/v1/partner/guide')
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.title', 'Insurtech Partner API Guide')
            ->assertJsonPath('data.endpoints.create_or_upsert_transaction.endpoint', '/api/v1/transactions');
    }
}
