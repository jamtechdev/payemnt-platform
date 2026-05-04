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
                                <h2 className="text-lg font-semibold text-slate-800">Swap Circle ↔ Insurtech — Partner API guide</h2>
                                <p className="text-sm text-slate-500">
                                    Urdu: Swap Circle jaisa koi bhi partner isi <strong>public base URL</strong> ({baseUrl}) par HTTPS APIs call karta hai.
                                    English: Same steps apply for any new partner; Swap is the reference implementation in{' '}
                                    <code className="rounded bg-white/80 px-1 text-xs">InsuretechSyncService.php</code>.
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
                            n="0"
                            title="Swagger (OpenAPI) — regenerate after PHP changes"
                            sub="Repo mein annotations update hone par docs dubara generate karein."
                        />
                    </CardHeader>
                    <CardContent className="space-y-2 text-xs text-slate-600">
                        <p>
                            Controllers under <code className="rounded bg-slate-100 px-1">app/Http/Controllers/Api/V1/</code> aur{' '}
                            <code className="rounded bg-slate-100 px-1">app/OpenApi/OpenApiSpec.php</code> se L5-Swagger JSON build hota hai.
                        </p>
                        <CodeBlock code={`cd admin-portal\nphp artisan l5-swagger:generate`} />
                        <p className="text-slate-500">Output: <code className="rounded bg-slate-100 px-1">storage/api-docs/api-docs.json</code> — Swagger UI isi file ko padhta hai.</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <SectionTitle
                            n="1"
                            title="Public JSON contract (no Bearer token)"
                            sub="GET /api/v1/partner/guide — production par APP_URL sahi hona zaroori hai."
                        />
                    </CardHeader>
                    <CardContent className="space-y-3">
                        <CodeBlock code={`GET ${partnerGuideUrl}`} />
                        <p className="text-xs text-slate-500">
                            Response <code className="rounded bg-slate-100 px-1">data</code> ke andar steps, endpoints, <code className="rounded bg-slate-100 px-1">public_base_url</code> milta hai — CI / partner onboarding scripts ke liye.
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <SectionTitle
                            n="2"
                            title="Insurtech admin side — Super Admin onboarding"
                            sub="Pehle yahan setup, phir partner app mein token lagao."
                        />
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm text-slate-600">
                        <ol className="list-decimal space-y-1.5 pl-5 text-xs leading-relaxed">
                            <li>Products banao / choose karo jo partners ko bechni hain.</li>
                            <li>
                                <strong>Partners</strong>: naya partner (active) — <code className="rounded bg-slate-100 px-1">partner_code</code> note karo.
                            </li>
                            <li>Us partner ko products assign karo (enabled).</li>
                            <li>
                                Partner detail → <strong>Generate API Key</strong>: jo token ek dafa dikhe, wohi{' '}
                                <code className="rounded bg-slate-100 px-1">Authorization: Bearer</code> value hai (Sanctum personal access token).
                            </li>
                        </ol>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <SectionTitle
                            n="3"
                            title="Swap Circle (ya koi partner app) — configuration"
                            sub="System Settings ya .env — base URL hamesha is Insurtech portal ka public origin."
                        />
                    </CardHeader>
                    <CardContent className="space-y-3">
                        <div className="overflow-auto rounded-lg border border-border">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/40 text-left text-xs text-muted-foreground">
                                        <th className="px-4 py-2 font-medium">Setting / ENV</th>
                                        <th className="px-4 py-2 font-medium">Example</th>
                                        <th className="px-4 py-2 font-medium">Note</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border/60">
                                    <tr>
                                        <td className="px-4 py-2 font-mono text-xs text-primary">INSURETECH_ADMIN_BASE_URL</td>
                                        <td className="px-4 py-2 text-xs text-slate-600">{baseUrl}</td>
                                        <td className="px-4 py-2 text-xs text-slate-500">Trailing slash avoid karein.</td>
                                    </tr>
                                    <tr>
                                        <td className="px-4 py-2 font-mono text-xs text-primary">INSURETECH_PARTNER_TOKEN</td>
                                        <td className="px-4 py-2 text-xs text-slate-600">(secret)</td>
                                        <td className="px-4 py-2 text-xs text-slate-500">Generate API Key se copy.</td>
                                    </tr>
                                    <tr>
                                        <td className="px-4 py-2 font-mono text-xs text-primary">INSURETECH_REQUEST_TIMEOUT</td>
                                        <td className="px-4 py-2 text-xs text-slate-600">20–30</td>
                                        <td className="px-4 py-2 text-xs text-slate-500">Seconds.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <SectionTitle
                            n="4"
                            title="Optional — POST /api/v1/verify (partner base URL register)"
                            sub="Bearer token nahi milta; sirf connected_base_url update hota hai."
                        />
                    </CardHeader>
                    <CardContent className="space-y-3">
                        <CodeBlock
                            code={`POST ${verifyUrl}
Content-Type: application/json

{
  "partner_code": "YOUR_PARTNER_CODE",
  "api_key": "plaintext key (admin ne jis waqt generate kiya)",
  "base_url": "https://partner.example.com"
}`}
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <SectionTitle
                            n="5"
                            title="Pull products — Insurtech → partner"
                            sub='Swap: admin "Pull Admin Products" → internally yeh Insurtech call.'
                        />
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="flex flex-wrap items-center gap-2 text-sm">
                            <span className="rounded bg-slate-100 px-2 py-1 font-mono text-xs">Swap Circle</span>
                            <ArrowRight className="h-4 w-4 shrink-0 text-slate-400" />
                            <span className="rounded bg-emerald-50 px-2 py-1 font-mono text-xs text-emerald-800">POST /api/insuretech/pull-products</span>
                            <ArrowRight className="h-4 w-4 shrink-0 text-slate-400" />
                            <span className="rounded bg-blue-50 px-2 py-1 font-mono text-xs text-blue-800">Insurtech</span>
                        </div>
                        <p className="text-xs font-medium text-slate-600">Insurtech (Bearer required):</p>
                        <CodeBlock code={`GET ${baseUrl}/api/v1/partner/products\nAuthorization: Bearer {INSURETECH_PARTNER_TOKEN}\nAccept: application/json`} />
                        <p className="text-xs text-slate-500">
                            Sample body shape (guide_price kabhi nahi aata):
                        </p>
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
                        <p className="text-xs text-slate-500">
                            Swap mapping: <code className="rounded bg-slate-100 px-1">products</code> + <code className="rounded bg-slate-100 px-1">it_product_mappings</code>.
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <SectionTitle
                            n="6"
                            title="Sale / policy — recommended (Swap production code)"
                            sub="POST .../submit (Idempotency-Key zaroori) phir POST .../kyc — NOT old POST /transactions only."
                        />
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="flex flex-wrap items-center gap-2 text-sm">
                            <span className="rounded bg-slate-100 px-2 py-1 font-mono text-xs">Swap Circle</span>
                            <ArrowRight className="h-4 w-4 shrink-0 text-slate-400" />
                            <span className="rounded bg-blue-50 px-2 py-1 font-mono text-xs text-blue-800">submit + kyc</span>
                        </div>
                        <div>
                            <p className="mb-1 text-xs font-medium text-slate-600">6a — Submit policy</p>
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
                            <p className="mb-1 text-xs font-medium text-slate-600">6b — KYC</p>
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
                            title="Connection test"
                            sub="Swap: GET /api/insuretech/test-connection → neeche wali Insurtech GET."
                        />
                    </CardHeader>
                    <CardContent className="space-y-3">
                        <CodeBlock
                            code={`GET /api/insuretech/test-connection   ← Swap internal

GET ${baseUrl}/api/v1/partner/products
Authorization: Bearer {INSURETECH_PARTNER_TOKEN}

# 200 + JSON → OK | 401 → token / partner | empty data → product assign check`}
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <SectionTitle
                            n="8"
                            title="Alternate — POST /api/v1/transactions (simple integrations)"
                            sub="Validation: customer_name + email + product_code + cover_duration required; optional Idempotency-Key = transaction_number."
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
                        <SectionTitle
                            n="9"
                            title="Other partner APIs (Swagger mein tags)"
                            sub="Bearer ke saath — customers, verify-token, policy update/cancel, webhook callback."
                        />
                    </CardHeader>
                    <CardContent className="space-y-3">
                        <div className="overflow-auto rounded-lg border border-border">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/40 text-left text-xs text-muted-foreground">
                                        <th className="px-4 py-2 font-medium">Purpose</th>
                                        <th className="px-4 py-2 font-medium">Method & path (Insurtech)</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border/60 text-xs">
                                    <tr>
                                        <td className="px-4 py-2 font-medium">Bearer sanity check</td>
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
                                        <td className="px-4 py-2 font-medium">Delete all partner customers (destructive)</td>
                                        <td className="px-4 py-2 font-mono text-amber-800">DELETE {baseUrl}/api/v1/customers</td>
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
                                        <td className="px-4 py-2 font-medium">Delete all partner transactions (destructive)</td>
                                        <td className="px-4 py-2 font-mono text-amber-800">DELETE {baseUrl}/api/v1/transactions</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Summary — Swap vs Insurtech</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-auto rounded-lg border border-border">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/40 text-left text-xs text-muted-foreground">
                                        <th className="px-4 py-2 font-medium">Action</th>
                                        <th className="px-4 py-2 font-medium">Swap Circle</th>
                                        <th className="px-4 py-2 font-medium">Insurtech</th>
                                        <th className="px-4 py-2 font-medium">Direction</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border/60 text-xs">
                                    <tr>
                                        <td className="px-4 py-2 font-medium">Pull products</td>
                                        <td className="px-4 py-2 font-mono text-slate-600">POST /api/insuretech/pull-products</td>
                                        <td className="px-4 py-2 font-mono text-blue-800">GET /api/v1/partner/products</td>
                                        <td className="px-4 py-2 text-emerald-700">Swap → Insurtech</td>
                                    </tr>
                                    <tr>
                                        <td className="px-4 py-2 font-medium">Push sale (as deployed)</td>
                                        <td className="px-4 py-2 font-mono text-slate-600">Auto / sync</td>
                                        <td className="px-4 py-2 font-mono text-blue-800">POST …/submit + POST …/kyc</td>
                                        <td className="px-4 py-2 text-blue-700">Swap → Insurtech</td>
                                    </tr>
                                    <tr>
                                        <td className="px-4 py-2 font-medium">Test connection</td>
                                        <td className="px-4 py-2 font-mono text-slate-600">GET /api/insuretech/test-connection</td>
                                        <td className="px-4 py-2 font-mono text-blue-800">GET /api/v1/partner/products</td>
                                        <td className="px-4 py-2 text-emerald-700">Swap → Insurtech</td>
                                    </tr>
                                    <tr>
                                        <td className="px-4 py-2 font-medium">JSON guide</td>
                                        <td className="px-4 py-2 text-slate-500">—</td>
                                        <td className="px-4 py-2 font-mono text-blue-800">GET /api/v1/partner/guide</td>
                                        <td className="px-4 py-2 text-slate-600">Public</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div className="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900">
                            <p className="font-medium">Pehle galat doc: sirf POST /transactions.</p>
                            <p className="mt-1 text-amber-800">
                                Asli Swap flow: <code className="rounded bg-amber-100 px-1">submit</code> + <code className="rounded bg-amber-100 px-1">kyc</code> zaroori hai jab tak aap consciously{' '}
                                <code className="rounded bg-amber-100 px-1">/transactions</code> use na karein.
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Live Swagger UI</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <iframe src={swaggerUrl} className="h-[min(90vh,900px)] w-full rounded border border-slate-200" title="Swagger UI" />
                    </CardContent>
                </Card>
            </div>
        </AdminLayout>
    );
}
