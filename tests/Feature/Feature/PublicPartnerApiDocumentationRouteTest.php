<?php

declare(strict_types=1);

namespace Tests\Feature\Feature;

use Tests\TestCase;

/** No RefreshDatabase — route is static; avoids SQLite driver requirement on CI-less PHP installs. */
class PublicPartnerApiDocumentationRouteTest extends TestCase
{
    public function test_partner_api_html_documentation_does_not_require_login(): void
    {
        $response = $this->get('/docs/partner-api');

        $response->assertOk();
        $this->assertFalse($response->isRedirect(), 'Public doc must not redirect to login');
    }
}
