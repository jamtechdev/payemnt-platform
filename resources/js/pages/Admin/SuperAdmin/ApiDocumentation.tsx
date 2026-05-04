import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import PublicApiDocLayout from '@/layouts/PublicApiDocLayout';
import { Button } from '@/components/ui/button';
import { Copy, ExternalLink } from 'lucide-react';

const copyText = (text: string) => navigator.clipboard.writeText(text);

function CodeBlock({ code }: { code: string }) {
    return (
        <div className="relative">
            <pre className="overflow-x-auto rounded-lg bg-slate-900 p-4 text-xs leading-relaxed text-green-400">{code}</pre>
            <button
                onClick={() => copyText(code)}
                className="absolute right-2 top-2 rounded p-1 text-slate-400 hover:text-white"
                title="Copy"
                type="button"
            >
                <Copy className="h-3.5 w-3.5" />
            </button>
        </div>
    );
}

function SectionTitle({ n, title, sub }: { n: string; title: string; sub: string }) {
    return (
        <div className="flex items-start gap-3">
            <span className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold text-primary-foreground">{n}</span>
            <div>
                <p className="font-semibold text-slate-800">{title}</p>
                <p className="text-xs text-slate-500">{sub}</p>
            </div>
        </div>
    );
}

export default function ApiDocumentation() {
    const baseUrl = window.location.origin;
    const swaggerUrl = `${baseUrl}/api/documentation`;
    const verifyUrl = `${baseUrl}/api/v1/verify`;

    return (
        <PublicApiDocLayout title="Partner API guide">
            <div className="space-y-6">
                <Card className="border-primary/20 bg-primary/5">
                    <CardContent className="pt-5">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p className="text-sm text-slate-600">
                                    This guide covers <strong>creating the connection</strong>, <strong>implementing a small HTTP client or service</strong> in your app, and calling Insurtech partner APIs (catalog, submit, KYC).
                                </p>
                            </div>
                            <div className="flex flex-wrap gap-2">
                                <a
                                    href={swaggerUrl}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="inline-flex items-center gap-1 rounded-md bg-emerald-600 px-3 py-2 text-sm text-white hover:bg-emerald-700"
                                >
                                    Open Swagger <ExternalLink className="h-3.5 w-3.5" />
                                </a>
                                <Button variant="outline" size="sm" onClick={() => copyText(swaggerUrl)}>
                                    Copy Swagger URL
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <SectionTitle
                            n="1"
                            title="What “connection” means"
                            sub="Before you write code, all of these must be true."
                        />
                    </CardHeader>
                    <CardContent className="space-y-3 text-sm text-slate-700">
                        <ol className="list-decimal space-y-2 pl-5 leading-relaxed">
                            <li>
                                <strong>Network:</strong> Your partner server can reach this portal at <code className="rounded bg-slate-100 px-1 text-xs">{baseUrl}</code> over HTTPS (or HTTP in local dev).
                            </li>
                            <li>
                                <strong>Credentials:</strong> You have a <strong>Bearer token</strong> issued for an <strong>active</strong> partner (Generate API Key in admin).
                            </li>
                            <li>
                                <strong>Catalog rights:</strong> At least one product is <strong>assigned and enabled</strong> for that partner, otherwise product list and submit paths return empty or 404.
                            </li>
                            <li>
                                <strong>Product mapping:</strong> Your app maps your local SKU to Insurtech <code className="rounded bg-slate-100 px-1 text-xs">product_code</code> from the catalog response before calling submit.
                            </li>
                        </ol>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Products: create in admin and share to the partner API</CardTitle>
                        <p className="mt-1 text-xs text-slate-500">
                            Partners never create products with the partner Bearer token — creation is Insurtech admin only. Sharing is assignment + access flags; the catalog is read from{' '}
                            <code className="rounded bg-slate-100 px-1 text-xs">GET /api/v1/partner/products</code>.
                        </p>
                    </CardHeader>
                    <CardContent className="space-y-4 text-sm text-slate-700">
                        <div>
                            <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Create (Super Admin UI)</p>
                            <ol className="list-decimal space-y-2 pl-5 leading-relaxed">
                                <li>
                                    In the admin portal go to <strong>Products</strong> → <strong>Create</strong> (route{' '}
                                    <code className="rounded bg-slate-100 px-1 text-xs">/admin/super-admin/products/create</code>).
                                </li>
                                <li>
                                    Pick a <strong>partner</strong> on the form (required). On save, Insurtech generates a stable <code className="rounded bg-slate-100 px-1 text-xs">product_code</code> from the name (uppercase slug + suffix) — that code is what your partner app must use in{' '}
                                    <code className="rounded bg-slate-100 px-1 text-xs">POST /api/v1/products/&#123;product_code&#125;/submit</code> and related paths.
                                </li>
                                <li>
                                    Set <strong>status</strong> to <strong>active</strong> for anything you want listed and sellable. Add <strong>dynamic fields</strong> (KYC / policy questions) as needed; Insurtech rebuilds the internal <code className="rounded bg-slate-100 px-1 text-xs">api_schema</code> when you save.
                                </li>
                                <li>
                                    Configure <strong>cover duration options</strong>, pricing, image, validation rules, and terms as required by your product.
                                </li>
                            </ol>
                        </div>
                        <div>
                            <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Share (who sees it in the API)</p>
                            <ul className="list-disc space-y-2 pl-5 leading-relaxed">
                                <li>
                                    <strong>Primary link on create:</strong> the partner you select on the product form is linked with access <strong>enabled</strong> automatically.
                                </li>
                                <li>
                                    <strong>More partners:</strong> open <strong>Partners</strong> → choose the partner → assign products and turn <strong>enabled</strong> on for each product you want them to sell.
                                </li>
                                <li>
                                    <code className="rounded bg-slate-100 px-1 text-xs">GET /api/v1/partner/products</code> returns <strong>active</strong> products only when that partner has an explicit pivot assignment with <strong>enabled</strong> access (<code className="rounded bg-slate-100 px-1 text-xs">partner_product.is_enabled = true</code>).
                                </li>
                            </ul>
                        </div>
                        <div className="rounded-md border border-amber-200/80 bg-amber-50/80 px-3 py-2 text-xs text-amber-950">
                            <strong>Partner token and product writes:</strong> <code className="rounded bg-white/80 px-1">POST /api/v1/partner/products</code> is blocked (403) — products are not created via the partner API. Optional{' '}
                            <code className="rounded bg-white/80 px-1">PUT /api/v1/partner/products/&#123;product_code&#125;</code> exists for limited metadata updates on partner-owned rows; automation for full CRUD uses separate{' '}
                            <code className="rounded bg-white/80 px-1">/api/v1/admin/products</code> with a <strong>Sanctum user</strong> session (super_admin), not the partner API key — see Swagger under admin routes if enabled.
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <SectionTitle
                            n="2"
                            title="Create the connection (Insurtech admin)"
                            sub="Super Admin — do this first; nothing works until the partner and token exist."
                        />
                    </CardHeader>
                    <CardContent>
                        <ol className="list-decimal space-y-2 pl-5 text-sm leading-relaxed text-slate-700">
                            <li>Create products (see <strong>Products: create in admin and share</strong> above) before or while onboarding partners.</li>
                            <li>
                                Go to <strong>Partners</strong> → <strong>Create</strong> (or edit) a partner. Set status to <strong>active</strong>. Save and copy <code className="rounded bg-slate-100 px-1 text-xs">partner_code</code> for optional verify calls.
                            </li>
                            <li>
                                On that partner, <strong>assign products</strong> and toggle them <strong>enabled</strong> for this partner (new partners do not get catalog visibility unless this is done).
                            </li>
                            <li>
                                Click <strong>Generate API Key</strong>. Copy the token immediately — this is the only time the raw Bearer token is shown. Store it in your partner app secrets (env, vault, or encrypted settings).
                            </li>
                            <li className="text-slate-600">
                                Optional: call <code className="rounded bg-slate-100 px-1 text-xs">POST /api/v1/verify</code> with <code className="rounded bg-slate-100 px-1 text-xs">partner_code</code>, plaintext <code className="rounded bg-slate-100 px-1 text-xs">api_key</code> from key creation, and your partner <code className="rounded bg-slate-100 px-1 text-xs">base_url</code> so Insurtech records where you connect from (does not return a new token).
                            </li>
                        </ol>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <SectionTitle
                            n="3"
                            title="Configure the partner application"
                            sub="Expose base URL, token, and timeout to your service layer."
                        />
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <p className="text-sm text-slate-700">
                            Store <strong>base URL</strong> and <strong>token</strong> in environment variables, a secrets manager, or your own database settings table — whichever fits your stack. Use one canonical base URL (no trailing slash) and one Bearer token string.
                        </p>
                        <div className="overflow-auto rounded-lg border border-border">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/40 text-left text-xs text-muted-foreground">
                                        <th className="px-4 py-2 font-medium">Variable</th>
                                        <th className="px-4 py-2 font-medium">Example</th>
                                        <th className="px-4 py-2 font-medium">Role</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border/60 text-xs text-slate-600">
                                    <tr>
                                        <td className="px-4 py-2 font-mono text-primary">INSURETECH_ADMIN_BASE_URL</td>
                                        <td className="px-4 py-2">{baseUrl}</td>
                                        <td className="px-4 py-2 text-slate-500">Scheme + host; no trailing slash.</td>
                                    </tr>
                                    <tr>
                                        <td className="px-4 py-2 font-mono text-primary">INSURETECH_PARTNER_TOKEN</td>
                                        <td className="px-4 py-2">(secret)</td>
                                        <td className="px-4 py-2 text-slate-500">Bearer token from step 2.</td>
                                    </tr>
                                    <tr>
                                        <td className="px-4 py-2 font-mono text-primary">INSURETECH_REQUEST_TIMEOUT</td>
                                        <td className="px-4 py-2">20–30</td>
                                        <td className="px-4 py-2 text-slate-500">HTTP client timeout (seconds).</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p className="text-xs font-medium text-slate-700">Optional Laravel config file (read values from env):</p>
                        <CodeBlock
                            code={`// config/insuretech.php — example (partner app)
return [
    'admin_base_url' => env('INSURETECH_ADMIN_BASE_URL', '${baseUrl}'),
    'partner_token' => env('INSURETECH_PARTNER_TOKEN', ''),
    'request_timeout_seconds' => (int) env('INSURETECH_REQUEST_TIMEOUT', 20),
];`}
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <SectionTitle
                            n="4"
                            title="Create the integration service (application code)"
                            sub="Encapsulate every Insurtech call behind one class or module."
                        />
                    </CardHeader>
                    <CardContent className="space-y-4 text-sm text-slate-700">
                        <div>
                            <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Responsibilities</p>
                            <ul className="list-disc space-y-1 pl-5 text-sm leading-relaxed">
                                <li>Load <strong>base URL</strong>, <strong>token</strong>, and <strong>timeout</strong> from config or secure storage.</li>
                                <li>Build a single HTTP client: <code className="rounded bg-slate-100 px-1 text-xs">Accept: application/json</code>, <code className="rounded bg-slate-100 px-1 text-xs">Authorization: Bearer …</code>.</li>
                                <li>Throw or return a clear error if base URL or token is missing (for example a <code className="rounded bg-slate-100 px-1 text-xs">RuntimeException</code> in PHP).</li>
                                <li>Implement <strong>testConnection</strong>, <strong>pullCatalog</strong>, <strong>submitPolicy</strong>, and <strong>submitKyc</strong> as thin wrappers over REST paths.</li>
                                <li>Map your internal product ID to <code className="rounded bg-slate-100 px-1 text-xs">product_code</code> before submit (persist catalog rows or a dedicated mapping table).</li>
                            </ul>
                        </div>
                        <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Laravel-style skeleton (pseudocode)</p>
                        <CodeBlock
                            code={`<?php
// app/Services/InsurtechPartnerClient.php (name as you prefer)

namespace App\\Services;

use Illuminate\\Support\\Facades\\Http;

class InsurtechPartnerClient
{
    private function settings(): array
    {
        $base = rtrim(config('insuretech.admin_base_url'), '/');
        $token = (string) config('insuretech.partner_token');
        $timeout = (int) config('insuretech.request_timeout_seconds', 20);
        if ($base === '' || $token === '') {
            throw new \\RuntimeException('Insurtech base URL or partner token missing.');
        }
        return compact('base', 'token', 'timeout');
    }

    private function client(): \\Illuminate\\Http\\Client\\PendingRequest
    {
        $s = $this->settings();
        return Http::baseUrl($s['base'])
            ->timeout($s['timeout'])
            ->acceptJson()
            ->withToken($s['token']);
    }

    public function testConnection(): array
    {
        $response = $this->client()->get('/api/v1/partner/products');
        if (! $response->successful()) {
            return ['ok' => false, 'status' => $response->status(), 'body' => $response->json()];
        }
        return ['ok' => true, 'status' => $response->status()];
    }

    public function pullCatalog(): array
    {
        $response = $this->client()->get('/api/v1/partner/products');
        // Parse response->json('data'), upsert local catalog + code mapping table
        return ['ok' => $response->successful(), 'payload' => $response->json()];
    }

    public function submitSale(string $productCode, array $body, string $idempotencyKey): array
    {
        $response = $this->client()
            ->withHeaders(['Idempotency-Key' => $idempotencyKey])
            ->post("/api/v1/products/{$productCode}/submit", $body);
        return ['ok' => $response->successful(), 'payload' => $response->json()];
    }

    public function submitKyc(string $productCode, string $transactionNumber, array $kyc): array
    {
        $response = $this->client()->post(
            "/api/v1/products/{$productCode}/transactions/{$transactionNumber}/kyc",
            ['kyc' => $kyc]
        );
        return ['ok' => $response->successful(), 'payload' => $response->json()];
    }
}`}
                        />
                        <p className="text-xs text-slate-600">
                            <strong>Wiring:</strong> Register the class in the container if needed, inject it into controllers or jobs, and call <code className="rounded bg-slate-100 px-1 text-xs">testConnection()</code> from an admin “Test Insurtech” button before enabling sync. After a successful purchase, call <code className="rounded bg-slate-100 px-1 text-xs">submitSale</code> then <code className="rounded bg-slate-100 px-1 text-xs">submitKyc</code> with the same <code className="rounded bg-slate-100 px-1 text-xs">transaction_number</code> returned or sent in the submit body.
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <SectionTitle
                            n="5"
                            title="Method → HTTP mapping (what your service should call)"
                            sub="Paths your integration should call on this portal."
                        />
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-auto rounded-lg border border-border">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/40 text-left text-xs text-muted-foreground">
                                        <th className="px-4 py-2 font-medium">Service method (suggested)</th>
                                        <th className="px-4 py-2 font-medium">HTTP</th>
                                        <th className="px-4 py-2 font-medium">Notes</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border/60 text-xs text-slate-700">
                                    <tr>
                                        <td className="px-4 py-2 font-mono text-slate-800">testConnection()</td>
                                        <td className="px-4 py-2 font-mono text-blue-800">GET /api/v1/partner/products</td>
                                        <td className="px-4 py-2 text-slate-600">Lightweight health check.</td>
                                    </tr>
                                    <tr>
                                        <td className="px-4 py-2 font-mono text-slate-800">pullCatalog()</td>
                                        <td className="px-4 py-2 font-mono text-blue-800">GET /api/v1/partner/products</td>
                                        <td className="px-4 py-2 text-slate-600">Then persist <code className="rounded bg-slate-100 px-1">product_code</code> locally.</td>
                                    </tr>
                                    <tr>
                                        <td className="px-4 py-2 font-mono text-slate-800">submitSale()</td>
                                        <td className="px-4 py-2 font-mono text-blue-800">POST /api/v1/products/&#123;code&#125;/submit</td>
                                        <td className="px-4 py-2 text-slate-600">Header <code className="rounded bg-slate-100 px-1">Idempotency-Key</code> required.</td>
                                    </tr>
                                    <tr>
                                        <td className="px-4 py-2 font-mono text-slate-800">submitKyc()</td>
                                        <td className="px-4 py-2 font-mono text-blue-800">POST …/transactions/&#123;txn&#125;/kyc</td>
                                        <td className="px-4 py-2 text-slate-600">JSON body with a <code className="rounded bg-slate-100 px-1">kyc</code> object.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <SectionTitle
                            n="6"
                            title="Validate the connection after implementation"
                            sub="Run these checks in order."
                        />
                    </CardHeader>
                    <CardContent className="space-y-4 text-sm text-slate-700">
                        <ol className="list-decimal space-y-2 pl-5 leading-relaxed">
                            <li>
                                From the partner server, run <code className="rounded bg-slate-100 px-1 text-xs">testConnection()</code> (GET products). Expect <strong>200</strong> and a JSON body with <code className="rounded bg-slate-100 px-1 text-xs">status: success</code>.
                            </li>
                            <li>
                                If you get <strong>401</strong>, the token is wrong, expired, or the partner is inactive — regenerate the key or re-copy the secret.
                            </li>
                            <li>
                                If <strong>200</strong> but <code className="rounded bg-slate-100 px-1 text-xs">data</code> is empty, fix product assignment on Insurtech for this partner.
                            </li>
                            <li>
                                Optional: <code className="rounded bg-slate-100 px-1 text-xs">GET {baseUrl}/api/v1/verify-token</code> with Bearer confirms the token is accepted and returns partner metadata.
                            </li>
                        </ol>
                        <p className="text-xs font-medium text-slate-600">Example (replace TOKEN):</p>
                        <CodeBlock
                            code={`curl -sS -H "Authorization: Bearer TOKEN" \\
  -H "Accept: application/json" \\
  "${baseUrl}/api/v1/partner/products"`}
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <SectionTitle
                            n="7"
                            title="Optional: POST /api/v1/verify"
                            sub="Registers partner base URL; does not issue Bearer token."
                        />
                    </CardHeader>
                    <CardContent className="space-y-3">
                        <CodeBlock
                            code={`POST ${verifyUrl}
Content-Type: application/json

{
  "partner_code": "YOUR_PARTNER_CODE",
  "api_key": "plaintext key from generation time",
  "base_url": "https://partner.example.com"
}`}
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <SectionTitle
                            n="8"
                            title="Catalog sync (GET products)"
                            sub="Call Insurtech directly from your backend or scheduled job."
                        />
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <p className="text-sm text-slate-700">
                            From your server, call Insurtech <strong>GET /api/v1/partner/products</strong> with the Bearer token. You may wrap this in your own internal route, queue worker, or cron — the contract is always this GET on the portal host.
                        </p>
                        <CodeBlock code={`GET ${baseUrl}/api/v1/partner/products\nAuthorization: Bearer {INSURETECH_PARTNER_TOKEN}\nAccept: application/json`} />
                        <CodeBlock
                            code={`{
  "status": "success",
  "data": [
    {
      "product_code": "NIGERIA_BENEFICIARY_COMMUNITY",
      "name": "Product Name",
      "description": "...",
      "price": 739.00,
      "status": "active",
      "image_url": "https://example.com/image.png"
    }
  ]
}`}
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <SectionTitle
                            n="9"
                            title="Record a sale (recommended flow)"
                            sub="Submit policy, then KYC."
                        />
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div>
                            <p className="mb-1 text-xs font-medium text-slate-700">9a — Submit</p>
                            <CodeBlock
                                code={`POST ${baseUrl}/api/v1/products/{product_code}/submit
Authorization: Bearer {INSURETECH_PARTNER_TOKEN}
Idempotency-Key: {unique_per_attempt}
Content-Type: application/json

{
  "transaction_number": "PARTNER-TXN-001",
  "customer_name": "Jane Doe",
  "customer_email": "jane@example.com",
  "phone": "+2348000000000",
  "cover_duration": "30_days",
  "status": "active",
  "notes": "Synced from partner",
  "amount": 739,
  "currency": "NGN"
}`}
                            />
                        </div>
                        <div>
                            <p className="mb-1 text-xs font-medium text-slate-700">9b — KYC</p>
                            <CodeBlock
                                code={`POST ${baseUrl}/api/v1/products/{product_code}/transactions/{transaction_number}/kyc
Authorization: Bearer {INSURETECH_PARTNER_TOKEN}
Content-Type: application/json

{
  "kyc": {
    "id_type": "phone",
    "id_number": "+2348000000000",
    "first_name": "Jane",
    "last_name": "Doe"
  }
}`}
                            />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <SectionTitle
                            n="10"
                            title="Health check (GET products)"
                            sub="Same endpoint as catalog pull; use for monitoring."
                        />
                    </CardHeader>
                    <CardContent className="space-y-3">
                        <CodeBlock
                            code={`GET ${baseUrl}/api/v1/partner/products
Authorization: Bearer {INSURETECH_PARTNER_TOKEN}
Accept: application/json`}
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Regenerate Swagger</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm text-slate-600">
                        <p className="text-xs">
                            After changing OpenAPI attributes under <code className="rounded bg-slate-100 px-1">app/Http/Controllers/Api/V1/</code> or{' '}
                            <code className="rounded bg-slate-100 px-1">app/OpenApi/</code>:
                        </p>
                        <CodeBlock code={`cd admin-portal\nphp artisan l5-swagger:generate`} />
                        <p className="text-xs text-slate-500">Output: <code className="rounded bg-slate-100 px-1">storage/api-docs/api-docs.json</code></p>
                        <p className="text-xs text-slate-600">
                            Use <strong>Open Swagger</strong> above for the full generated catalog. This page focuses on the main distribution flow (catalog, submit, KYC).
                        </p>
                    </CardContent>
                </Card>
            </div>
        </PublicApiDocLayout>
    );
}
