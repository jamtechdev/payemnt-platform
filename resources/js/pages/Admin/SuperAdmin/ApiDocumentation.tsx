import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AdminLayout from '@/layouts/AdminLayout';
import { Button } from '@/components/ui/button';
import { Copy, ExternalLink, ArrowRight } from 'lucide-react';

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
    const partnerGuideUrl = `${baseUrl}/api/v1/partner/guide`;
    const verifyUrl = `${baseUrl}/api/v1/verify`;

    return (
        <AdminLayout title="API Guide">
            <div className="space-y-6">
                <Card className="border-primary/20 bg-primary/5">
                    <CardContent className="pt-5">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h2 className="text-lg font-semibold text-slate-800">Partner API integration</h2>
                                <p className="text-sm text-slate-600">
                                    This page walks through <strong>creating the connection</strong>, <strong>implementing a service</strong> in your app, and calling Insurtech APIs. Reference:{' '}
                                    <code className="rounded bg-white/80 px-1 text-xs">swap-circle/app/services/InsuretechSyncService.php</code>.
                                </p>
                            </div>
                            <div className="flex flex-wrap gap-2">
                                <a
                                    href={partnerGuideUrl}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="inline-flex items-center gap-1 rounded-md bg-slate-800 px-3 py-2 text-sm text-white hover:bg-slate-900"
                                >
                                    JSON guide <ExternalLink className="h-3.5 w-3.5" />
                                </a>
                                <a
                                    href={swaggerUrl}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="inline-flex items-center gap-1 rounded-md bg-emerald-600 px-3 py-2 text-sm text-white hover:bg-emerald-700"
                                >
                                    Swagger UI <ExternalLink className="h-3.5 w-3.5" />
                                </a>
                                <Button variant="outline" size="sm" onClick={() => copyText(partnerGuideUrl)}>
                                    Copy JSON guide URL
                                </Button>
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
                        <SectionTitle
                            n="2"
                            title="Create the connection (Insurtech admin)"
                            sub="Super Admin — do this first; nothing works until the partner and token exist."
                        />
                    </CardHeader>
                    <CardContent>
                        <ol className="list-decimal space-y-2 pl-5 text-sm leading-relaxed text-slate-700">
                            <li>Create the products you want partners to distribute.</li>
                            <li>
                                Go to <strong>Partners</strong> → <strong>Create</strong> (or edit) a partner. Set status to <strong>active</strong>. Save and copy <code className="rounded bg-slate-100 px-1 text-xs">partner_code</code> for optional verify calls.
                            </li>
                            <li>
                                On that partner, <strong>assign products</strong> and toggle them <strong>enabled</strong> for this partner.
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
                            Swap reads <strong>database system_settings</strong> first, then falls back to <strong>.env</strong> (see <code className="rounded bg-slate-100 px-1 text-xs">getRuntimeSetting</code> in the reference service). Your app can use only env vars or only DB — the pattern is the same: one canonical base URL and one token string.
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
                        <p className="text-xs font-medium text-slate-700">Optional Laravel config file (defaults only; Swap uses this plus runtime overrides):</p>
                        <CodeBlock
                            code={`// config/insuretech.php — example defaults
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
                                <li>Throw or return a clear error if base URL or token is missing (Swap throws <code className="rounded bg-slate-100 px-1 text-xs">RuntimeException</code>).</li>
                                <li>Implement <strong>testConnection</strong>, <strong>pullCatalog</strong>, <strong>submitPolicy</strong>, <strong>submitKyc</strong> (and optional batch sync) as thin wrappers over REST paths.</li>
                                <li>Map your internal product ID to <code className="rounded bg-slate-100 px-1 text-xs">product_code</code> before submit (Swap uses <code className="rounded bg-slate-100 px-1 text-xs">it_product_mappings</code>).</li>
                            </ul>
                        </div>
                        <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Laravel-style skeleton (pseudocode — mirror Swap structure)</p>
                        <CodeBlock
                            code={`<?php
// app/Services/InsurtechPartnerClient.php (name as you prefer)

namespace App\\Services;

use Illuminate\\Support\\Facades\\Http;

class InsurtechPartnerClient
{
    private function settings(): array
    {
        $base = rtrim(config('insurtech.admin_base_url'), '/');
        $token = (string) config('insurtech.partner_token');
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
                            <strong>Wiring:</strong> Register the class in the container if needed, inject it into controllers or jobs, and call <code className="rounded bg-slate-100 px-1 text-xs">testConnection()</code> from an admin “Test Insurtech” button before enabling sync. On purchase webhooks or queue workers, call <code className="rounded bg-slate-100 px-1 text-xs">submitSale</code> then <code className="rounded bg-slate-100 px-1 text-xs">submitKyc</code> with the same <code className="rounded bg-slate-100 px-1 text-xs">transaction_number</code> returned or sent in the submit body.
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <SectionTitle
                            n="5"
                            title="Method → HTTP mapping (what your service should call)"
                            sub="Same paths Swap uses in production."
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
                                    <tr>
                                        <td className="px-4 py-2 font-mono text-slate-800">ingestTransaction() (optional)</td>
                                        <td className="px-4 py-2 font-mono text-blue-800">POST /api/v1/transactions</td>
                                        <td className="px-4 py-2 text-slate-600">Single-shot alternative to submit+kyc.</td>
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
                            title="Public machine-readable contract"
                            sub="No Bearer token — for CI and partner onboarding automation."
                        />
                    </CardHeader>
                    <CardContent className="space-y-3">
                        <CodeBlock code={`GET ${partnerGuideUrl}`} />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <SectionTitle
                            n="8"
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
                            n="9"
                            title="Catalog sync (GET products)"
                            sub="Swap: POST /api/insuretech/pull-products → same Insurtech GET below."
                        />
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="flex flex-wrap items-center gap-2 text-sm text-slate-700">
                            <span className="rounded bg-slate-100 px-2 py-1 font-mono text-xs">Partner app</span>
                            <ArrowRight className="h-4 w-4 shrink-0 text-slate-400" />
                            <span className="rounded bg-emerald-50 px-2 py-1 font-mono text-xs text-emerald-800">POST /api/insuretech/pull-products</span>
                            <ArrowRight className="h-4 w-4 shrink-0 text-slate-400" />
                            <span className="rounded bg-blue-50 px-2 py-1 font-mono text-xs text-blue-800">Insurtech</span>
                        </div>
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
                            n="10"
                            title="Record a sale (recommended — Swap production)"
                            sub="Submit policy, then KYC."
                        />
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div>
                            <p className="mb-1 text-xs font-medium text-slate-700">10a — Submit</p>
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
                            <p className="mb-1 text-xs font-medium text-slate-700">10b — KYC</p>
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
                            n="11"
                            title="Health check from Swap (reference)"
                            sub="Internal route forwards to the same GET products call."
                        />
                    </CardHeader>
                    <CardContent className="space-y-3">
                        <CodeBlock
                            code={`GET /api/insuretech/test-connection   (Swap Circle)

GET ${baseUrl}/api/v1/partner/products
Authorization: Bearer {INSURETECH_PARTNER_TOKEN}`}
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <SectionTitle
                            n="12"
                            title="Alternative: POST /api/v1/transactions"
                            sub="Single request; see Swagger for all fields."
                        />
                    </CardHeader>
                    <CardContent className="space-y-3">
                        <CodeBlock
                            code={`POST ${baseUrl}/api/v1/transactions
Authorization: Bearer {INSURETECH_PARTNER_TOKEN}
Content-Type: application/json

{
  "transaction_number": "PARTNER-TXN-001",
  "customer_name": "John Doe",
  "customer_email": "john@example.com",
  "product_code": "YOUR_PRODUCT_CODE",
  "cover_duration": "30_days",
  "status": "active",
  "date_added": "2026-05-04 10:00:00"
}`}
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Other partner endpoints (Bearer)</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-auto rounded-lg border border-border">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/40 text-left text-xs text-muted-foreground">
                                        <th className="px-4 py-2 font-medium">Purpose</th>
                                        <th className="px-4 py-2 font-medium">Method and URL</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border/60 text-xs text-slate-700">
                                    <tr>
                                        <td className="px-4 py-2 font-medium">Validate token</td>
                                        <td className="px-4 py-2 font-mono text-blue-800">GET {baseUrl}/api/v1/verify-token</td>
                                    </tr>
                                    <tr>
                                        <td className="px-4 py-2 font-medium">Register customer</td>
                                        <td className="px-4 py-2 font-mono text-blue-800">POST {baseUrl}/api/v1/customers/register</td>
                                    </tr>
                                    <tr>
                                        <td className="px-4 py-2 font-medium">Update customer</td>
                                        <td className="px-4 py-2 font-mono text-blue-800">PUT {baseUrl}/api/v1/customers/&lt;customer_code&gt;</td>
                                    </tr>
                                    <tr>
                                        <td className="px-4 py-2 font-medium">Update policy</td>
                                        <td className="px-4 py-2 font-mono text-blue-800">PUT {baseUrl}/api/v1/products/&lt;code&gt;/transactions/&lt;txn&gt;</td>
                                    </tr>
                                    <tr>
                                        <td className="px-4 py-2 font-medium">Cancel policy</td>
                                        <td className="px-4 py-2 font-mono text-blue-800">POST {baseUrl}/api/v1/products/&lt;code&gt;/transactions/&lt;txn&gt;/cancel</td>
                                    </tr>
                                    <tr>
                                        <td className="px-4 py-2 font-medium">Callback (signed)</td>
                                        <td className="px-4 py-2 font-mono text-blue-800">POST {baseUrl}/api/v1/products/&lt;code&gt;/transactions/&lt;txn&gt;/callback</td>
                                    </tr>
                                    <tr>
                                        <td className="px-4 py-2 font-medium text-amber-900">Delete all customers</td>
                                        <td className="px-4 py-2 font-mono text-amber-800">DELETE {baseUrl}/api/v1/customers</td>
                                    </tr>
                                    <tr>
                                        <td className="px-4 py-2 font-medium text-amber-900">Delete all transactions</td>
                                        <td className="px-4 py-2 font-mono text-amber-800">DELETE {baseUrl}/api/v1/transactions</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Swap Circle vs Insurtech (quick map)</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-auto rounded-lg border border-border">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/40 text-left text-xs text-muted-foreground">
                                        <th className="px-4 py-2 font-medium">Action</th>
                                        <th className="px-4 py-2 font-medium">Swap Circle</th>
                                        <th className="px-4 py-2 font-medium">Insurtech</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border/60 text-xs text-slate-700">
                                    <tr>
                                        <td className="px-4 py-2 font-medium">Pull catalog</td>
                                        <td className="px-4 py-2 font-mono text-slate-600">POST /api/insuretech/pull-products</td>
                                        <td className="px-4 py-2 font-mono text-blue-800">GET /api/v1/partner/products</td>
                                    </tr>
                                    <tr>
                                        <td className="px-4 py-2 font-medium">Push sale</td>
                                        <td className="px-4 py-2 text-slate-600">InsuretechSyncService</td>
                                        <td className="px-4 py-2 font-mono text-blue-800">POST …/submit + POST …/kyc</td>
                                    </tr>
                                    <tr>
                                        <td className="px-4 py-2 font-medium">Health</td>
                                        <td className="px-4 py-2 font-mono text-slate-600">GET /api/insuretech/test-connection</td>
                                        <td className="px-4 py-2 font-mono text-blue-800">GET /api/v1/partner/products</td>
                                    </tr>
                                    <tr>
                                        <td className="px-4 py-2 font-medium">JSON guide</td>
                                        <td className="px-4 py-2 text-slate-500">—</td>
                                        <td className="px-4 py-2 font-mono text-blue-800">GET /api/v1/partner/guide</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
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
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Swagger UI</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <iframe src={swaggerUrl} className="h-[min(90vh,900px)] w-full rounded border border-slate-200" title="Swagger UI" />
                    </CardContent>
                </Card>
            </div>
        </AdminLayout>
    );
}
