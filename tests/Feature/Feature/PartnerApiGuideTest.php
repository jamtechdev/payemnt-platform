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
        $response = $this->getJson('/api/v1/partner/guide');
        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.title', 'Insurtech Partner API Guide')
            ->assertJsonPath('data.suggested_partner_service.suggested_methods.0.name', 'testConnection');
        $response->assertJsonCount(4, 'data.connection_prerequisites');
        $response->assertJsonCount(7, 'data.integration_steps');

        $htmlDocUrl = (string) data_get($response->json(), 'data.urls.partner_api_html_doc');
        $this->assertStringContainsString('/admin/super-admin/api-documentation', $htmlDocUrl);
    }
}
