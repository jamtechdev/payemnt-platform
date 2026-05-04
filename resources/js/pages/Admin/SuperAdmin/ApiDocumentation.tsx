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
                                    External apps call this portal at <strong>{baseUrl}</strong> over HTTPS. Swap Circle is the reference
                                    implementation: <code className="rounded bg-white/80 px-1 text-xs">swap-circle/app/services/InsuretechSyncService.php</code>{' '}
                                    (HTTP client: base URL + <code className="rounded bg-white/80 px-1 text-xs">Authorization: Bearer</code> token).
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
                            title="Prepare the partner on this admin portal"
                            sub="Super Admin actions before any partner traffic."
                        />
                    </CardHeader>
                    <CardContent>
                        <ol className="list-decimal space-y-2 pl-5 text-sm leading-relaxed text-slate-700">
                            <li>Create products that partners may sell.</li>
                            <li>
                                Open <strong>Partners</strong>, create an <strong>active</strong> partner, and note <code className="rounded bg-slate-100 px-1 text-xs">partner_code</code>.
                            </li>
                            <li>Assign products to that partner and enable access.</li>
                            <li>
                                On the partner detail screen, use <strong>Generate API Key</strong>. Copy the token once — that string is the Bearer token
                                (Sanctum personal access token for the partner).
                            </li>
                        </ol>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <SectionTitle
                            n="2"
                            title="Configure the partner application"
                            sub="Same pattern as Swap Circle: system settings and/or environment variables."
                        />
                    </CardHeader>
                    <CardContent className="space-y-3">
                        <div className="overflow-auto rounded-lg border border-border">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/40 text-left text-xs text-muted-foreground">
                                        <th className="px-4 py-2 font-medium">Variable</th>
                                        <th className="px-4 py-2 font-medium">Example</th>
                                        <th className="px-4 py-2 font-medium">Description</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border/60 text-xs text-slate-600">
                                    <tr>
                                        <td className="px-4 py-2 font-mono text-primary">INSURETECH_ADMIN_BASE_URL</td>
                                        <td className="px-4 py-2">{baseUrl}</td>
                                        <td className="px-4 py-2 text-slate-500">Public origin of this portal; no trailing slash.</td>
                                    </tr>
                                    <tr>
                                        <td className="px-4 py-2 font-mono text-primary">INSURETECH_PARTNER_TOKEN</td>
                                        <td className="px-4 py-2">(secret)</td>
                                        <td className="px-4 py-2 text-slate-500">Bearer token from Generate API Key.</td>
                                    </tr>
                                    <tr>
                                        <td className="px-4 py-2 font-mono text-primary">INSURETECH_REQUEST_TIMEOUT</td>
                                        <td className="px-4 py-2">20–30</td>
                                        <td className="px-4 py-2 text-slate-500">HTTP timeout in seconds.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <SectionTitle
                            n="3"
                            title="Fetch the machine-readable guide (no authentication)"
                            sub="Useful for onboarding scripts; APP_URL must match the public deployment."
                        />
                    </CardHeader>
                    <CardContent className="space-y-3">
                        <CodeBlock code={`GET ${partnerGuideUrl}`} />
                        <p className="text-xs text-slate-600">
                            Response includes <code className="rounded bg-slate-100 px-1">data</code> with steps, endpoint paths, and <code className="rounded bg-slate-100 px-1">public_base_url</code>.
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <SectionTitle
                            n="4"
                            title="Optional: register the partner base URL"
                            sub="Does not return a Bearer token; updates stored connection metadata."
                        />
                    </CardHeader>
                    <CardContent className="space-y-3">
                        <CodeBlock
                            code={`POST ${verifyUrl}
Content-Type: application/json

{
  "partner_code": "YOUR_PARTNER_CODE",
  "api_key": "plaintext key from the moment it was generated in admin",
  "base_url": "https://partner.example.com"
}`}
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <SectionTitle
                            n="5"
                            title="Sync the product catalog"
                            sub="Swap calls Insurtech from pull-products; your app can call Insurtech directly the same way."
                        />
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="flex flex-wrap items-center gap-2 text-sm text-slate-700">
                            <span className="rounded bg-slate-100 px-2 py-1 font-mono text-xs">Swap Circle</span>
                            <ArrowRight className="h-4 w-4 shrink-0 text-slate-400" />
                            <span className="rounded bg-emerald-50 px-2 py-1 font-mono text-xs text-emerald-800">POST /api/insuretech/pull-products</span>
                            <ArrowRight className="h-4 w-4 shrink-0 text-slate-400" />
                            <span className="rounded bg-blue-50 px-2 py-1 font-mono text-xs text-blue-800">Insurtech</span>
                        </div>
                        <p className="text-xs font-medium text-slate-700">Insurtech endpoint:</p>
                        <CodeBlock code={`GET ${baseUrl}/api/v1/partner/products\nAuthorization: Bearer {INSURETECH_PARTNER_TOKEN}\nAccept: application/json`} />
                        <p className="text-xs text-slate-600">
                            Swap persists rows to <code className="rounded bg-slate-100 px-1">products</code> and <code className="rounded bg-slate-100 px-1">it_product_mappings</code>. <code className="rounded bg-slate-100 px-1">guide_price</code> is never returned on partner APIs.
                        </p>
                        <p className="text-xs text-slate-500">Example success shape:</p>
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
                            n="6"
                            title="Record a sale (Swap production flow)"
                            sub="Submit policy, then KYC. Use admin product_code from the catalog response."
                        />
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div>
                            <p className="mb-1 text-xs font-medium text-slate-700">6a — Submit policy (Idempotency-Key header is required)</p>
                            <CodeBlock
                                code={`POST ${baseUrl}/api/v1/products/{product_code}/submit
Authorization: Bearer {INSURETECH_PARTNER_TOKEN}
Idempotency-Key: {unique_per_attempt}
Content-Type: application/json

{
  "transaction_number": "SWAP-12345-abc123",
  "customer_name": "Jane Doe",
  "customer_email": "jane@example.com",
  "phone": "+2348000000000",
  "cover_duration": "30_days",
  "status": "active",
  "notes": "Synced from swap-circle",
  "amount": 739,
  "currency": "NGN"
}`}
                            />
                        </div>
                        <div>
                            <p className="mb-1 text-xs font-medium text-slate-700">6b — Submit KYC for that transaction</p>
                            <CodeBlock
                                code={`POST ${baseUrl}/api/v1/products/{product_code}/transactions/{transaction_number}/kyc
Authorization: Bearer {INSURETECH_PARTNER_TOKEN}
Content-Type: application/json

{
  "kyc": {
    "id_type": "phone",
    "id_number": "+2348000000000",
    "first_name": "Jane",
    "last_name": "Doe",
    "dob": "",
    "address": ""
  }
}`}
                            />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <SectionTitle
                            n="7"
                            title="Verify connectivity"
                            sub="Swap exposes GET /api/insuretech/test-connection; it uses the same Insurtech call as catalog sync."
                        />
                    </CardHeader>
                    <CardContent className="space-y-3">
                        <CodeBlock
                            code={`GET /api/insuretech/test-connection
  (Swap Circle internal route)

GET ${baseUrl}/api/v1/partner/products
Authorization: Bearer {INSURETECH_PARTNER_TOKEN}

Expect HTTP 200 with JSON. 401 means invalid or inactive token. Empty product list means assignments are missing.`}
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <SectionTitle
                            n="8"
                            title="Alternative: single-shot transaction ingest"
                            sub="Optional path for simpler integrations. Swap uses step 6 instead."
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
                        <p className="text-xs text-slate-600">
                            If you send <code className="rounded bg-slate-100 px-1">Idempotency-Key</code>, it must equal <code className="rounded bg-slate-100 px-1">transaction_number</code>. See Swagger for full field list.
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <SectionTitle
                            n="9"
                            title="Other authenticated partner endpoints"
                            sub="Bearer token required unless noted. Full request bodies are in Swagger UI."
                        />
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
                                        <td className="px-4 py-2 font-medium">Validate Bearer token</td>
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
                                        <td className="px-4 py-2 font-medium">Inbound callback (signed)</td>
                                        <td className="px-4 py-2 font-mono text-blue-800">POST {baseUrl}/api/v1/products/&lt;code&gt;/transactions/&lt;txn&gt;/callback</td>
                                    </tr>
                                    <tr>
                                        <td className="px-4 py-2 font-medium text-amber-900">Delete all customers (destructive)</td>
                                        <td className="px-4 py-2 font-mono text-amber-800">DELETE {baseUrl}/api/v1/customers</td>
                                    </tr>
                                    <tr>
                                        <td className="px-4 py-2 font-medium text-amber-900">Delete all transactions (destructive)</td>
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
                                        <td className="px-4 py-2 text-slate-600">Purchase / sync jobs</td>
                                        <td className="px-4 py-2 font-mono text-blue-800">POST …/submit then POST …/kyc</td>
                                    </tr>
                                    <tr>
                                        <td className="px-4 py-2 font-medium">Health check</td>
                                        <td className="px-4 py-2 font-mono text-slate-600">GET /api/insuretech/test-connection</td>
                                        <td className="px-4 py-2 font-mono text-blue-800">GET /api/v1/partner/products</td>
                                    </tr>
                                    <tr>
                                        <td className="px-4 py-2 font-medium">Public guide JSON</td>
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
                            <code className="rounded bg-slate-100 px-1">app/OpenApi/</code>, run:
                        </p>
                        <CodeBlock code={`cd admin-portal\nphp artisan l5-swagger:generate`} />
                        <p className="text-xs text-slate-500">Writes <code className="rounded bg-slate-100 px-1">storage/api-docs/api-docs.json</code> consumed by Swagger UI below.</p>
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
