import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Copy, ExternalLink } from 'lucide-react';

export default function ApiDocumentation() {
    const baseUrl = window.location.origin;
    const productsEndpoint = `${baseUrl}/api/v1/partner/products`;
    const schemaEndpoint = `${baseUrl}/api/v1/partner/products/{uuid}/schema`;
    const transactionEndpoint = `${baseUrl}/api/v1/transactions`;
    const machineReadableGuide = `${baseUrl}/api/v1/partner/guide`;
    const swaggerEndpoint = `${baseUrl}/api/documentation`;

    const copyToClipboard = (text: string) => navigator.clipboard.writeText(text);

    const transactionPayload = `{
  "transaction_number": "SWAP-TXN-1001",
  "customer_name": "John Doe",
  "customer_email": "john.doe@example.com",
  "product_code": "NIGERIA_BENEFICIARY_COMMUNITY",
  "cover_duration": "12_months",
  "status": "active",
  "notes": "Captured from swap checkout",
  "amount": 10000,
  "currency": "NGN",
  "date_added": "2026-05-01 10:00:00"
}`;

    const verifyCurl = `curl -X POST "${baseUrl}/api/v1/verify" \\
  -H "Content-Type: application/json" \\
  -d '{
    "partner_code": "SWAP_CIRCLE",
    "api_key": "YOUR_PARTNER_API_KEY",
    "base_url": "https://swapcircle.com"
  }'`;

    const productsCurl = `curl -X GET "${productsEndpoint}" \\
  -H "Authorization: Bearer YOUR_PARTNER_TOKEN" \\
  -H "Accept: application/json"`;

    const schemaCurl = `curl -X GET "${baseUrl}/api/v1/partner/products/{uuid}/schema" \\
  -H "Authorization: Bearer YOUR_PARTNER_TOKEN" \\
  -H "Accept: application/json"`;

    const transactionCurl = `curl -X POST "${transactionEndpoint}" \\
  -H "Authorization: Bearer YOUR_PARTNER_TOKEN" \\
  -H "Idempotency-Key: SWAP-TXN-1001" \\
  -H "Content-Type: application/json" \\
  -d '{
    "transaction_number": "SWAP-TXN-1001",
    "customer_name": "John Doe",
    "customer_email": "john.doe@example.com",
    "product_code": "NIGERIA_BENEFICIARY_COMMUNITY",
    "cover_duration": "12_months",
    "status": "active",
    "notes": "Captured from swap checkout",
    "amount": 10000,
    "currency": "NGN",
    "date_added": "2026-05-01 10:00:00"
  }'`;

    const partnerEnv = `# Partner (Swap) environment
INSURETECH_ADMIN_BASE_URL=http://127.0.0.1:8000
INSURETECH_PARTNER_TOKEN=PASTE_PARTNER_BEARER_TOKEN_HERE
INSURETECH_REQUEST_TIMEOUT=20
INSURETECH_AUTO_PULL_BEFORE_PUSH=true
INSURETECH_DEFAULT_SYNC_LIMIT=25
INSURETECH_MAX_SYNC_LIMIT=200`;

    const adminEnvNotes = `# Admin portal values used for partner onboarding
# (Configured by admin team; not shared directly as .env file)
- Partner Code (example: SWAP_CIRCLE)
- Generated Partner API Token (share securely with partner backend only)
- Assigned Product Codes (example: NIGERIA_BENEFICIARY_COMMUNITY)
- Active product schema fields from /api/v1/partner/products/{uuid}/schema`;

    return (
        <div className="min-h-screen bg-slate-50 px-4 py-8 md:px-8">
            <div className="mx-auto max-w-5xl space-y-6">
                <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h1 className="text-2xl font-bold text-slate-900">Insurtech Partner API Documentation</h1>
                    <p className="mt-2 text-sm text-slate-600">
                        Full step-by-step partner guide with endpoint details, payload examples, and ready-to-use cURL commands.
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Overview</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3 text-sm text-slate-700">
                        <p><strong>Base URL:</strong> <code>{baseUrl}/api/v1</code></p>
                        <p><strong>Authentication:</strong> Bearer token per partner.</p>
                        <p><strong>Data flow:</strong> Admin creates products, partner pulls products, partner pushes transactions on sales.</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Step 1 - Admin Onboarding for Partner</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3 text-sm text-slate-700">
                        <ol className="list-decimal space-y-2 pl-5">
                            <li>Create partner (name, code, email, phone) in admin portal.</li>
                            <li>Create products in admin portal and configure product fields.</li>
                            <li>Assign enabled products to the partner.</li>
                            <li>Generate partner token from admin and share securely.</li>
                        </ol>
                        <pre className="rounded bg-slate-100 p-3 font-mono text-xs">Authorization: Bearer {'{PARTNER_TOKEN}'}</pre>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Step 1.1 - Environment Configuration</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4 text-sm text-slate-700">
                        <p><strong>Partner backend .env (required):</strong></p>
                        <div className="rounded bg-slate-900 p-4 text-green-400">
                            <div className="mb-2 flex items-center justify-between">
                                <span className="text-xs text-slate-300">.env Example</span>
                                <Button size="sm" variant="ghost" onClick={() => copyToClipboard(partnerEnv)} className="text-slate-300 hover:text-white">
                                    <Copy className="h-4 w-4" />
                                </Button>
                            </div>
                            <pre className="overflow-x-auto text-xs">{partnerEnv}</pre>
                        </div>

                        <p><strong>Admin must provide these onboarding values:</strong></p>
                        <div className="rounded bg-slate-100 p-3">
                            <pre className="overflow-x-auto text-xs text-slate-700">{adminEnvNotes}</pre>
                        </div>

                        <p className="text-xs text-slate-500">
                            Important: Keep `INSURETECH_PARTNER_TOKEN` server-side only. Do not expose in frontend/mobile apps.
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Step 2 - Verify Partner Setup</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3 text-sm text-slate-700">
                        <pre className="rounded bg-slate-100 p-3 font-mono text-xs">POST {baseUrl}/api/v1/verify</pre>
                        <div className="rounded bg-slate-900 p-4 text-green-400">
                            <div className="mb-2 flex items-center justify-between">
                                <span className="text-xs text-slate-300">cURL</span>
                                <Button size="sm" variant="ghost" onClick={() => copyToClipboard(verifyCurl)} className="text-slate-300 hover:text-white">
                                    <Copy className="h-4 w-4" />
                                </Button>
                            </div>
                            <pre className="overflow-x-auto text-xs">{verifyCurl}</pre>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Step 3 - Pull Products Assigned by Admin</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3 text-sm text-slate-700">
                        <p>Partner can only consume products created by admin and assigned to that partner.</p>
                        <pre className="rounded bg-slate-100 p-3 font-mono text-xs">GET {productsEndpoint}</pre>
                        <div className="rounded bg-slate-900 p-4 text-green-400">
                            <div className="mb-2 flex items-center justify-between">
                                <span className="text-xs text-slate-300">cURL</span>
                                <Button size="sm" variant="ghost" onClick={() => copyToClipboard(productsCurl)} className="text-slate-300 hover:text-white">
                                    <Copy className="h-4 w-4" />
                                </Button>
                            </div>
                            <pre className="overflow-x-auto text-xs">{productsCurl}</pre>
                        </div>
                        <p className="text-xs text-slate-500">Partner-side product creation is disabled by design.</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Step 4 - Fetch Product Schema</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3 text-sm text-slate-700">
                        <pre className="rounded bg-slate-100 p-3 font-mono text-xs">GET {schemaEndpoint}</pre>
                        <div className="rounded bg-slate-900 p-4 text-green-400">
                            <div className="mb-2 flex items-center justify-between">
                                <span className="text-xs text-slate-300">cURL</span>
                                <Button size="sm" variant="ghost" onClick={() => copyToClipboard(schemaCurl)} className="text-slate-300 hover:text-white">
                                    <Copy className="h-4 w-4" />
                                </Button>
                            </div>
                            <pre className="overflow-x-auto text-xs">{schemaCurl}</pre>
                        </div>
                        <p>Build partner-side form using returned schema fields.</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Step 5 - Push Transaction on Every Sale</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3 text-sm text-slate-700">
                        <pre className="rounded bg-slate-100 p-3 font-mono text-xs">POST {transactionEndpoint}</pre>
                        <p>Use `Idempotency-Key` header equal to `transaction_number`.</p>
                        <div className="rounded bg-slate-900 p-4 text-green-400">
                            <div className="mb-2 flex items-center justify-between">
                                <span className="text-xs text-slate-300">Example JSON Payload</span>
                                <Button size="sm" variant="ghost" onClick={() => copyToClipboard(transactionPayload)} className="text-slate-300 hover:text-white">
                                    <Copy className="h-4 w-4" />
                                </Button>
                            </div>
                            <pre className="overflow-x-auto text-xs">{transactionPayload}</pre>
                        </div>
                        <div className="rounded bg-slate-900 p-4 text-green-400">
                            <div className="mb-2 flex items-center justify-between">
                                <span className="text-xs text-slate-300">cURL</span>
                                <Button size="sm" variant="ghost" onClick={() => copyToClipboard(transactionCurl)} className="text-slate-300 hover:text-white">
                                    <Copy className="h-4 w-4" />
                                </Button>
                            </div>
                            <pre className="overflow-x-auto text-xs">{transactionCurl}</pre>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Step 6 - Validate on Admin Portal</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm text-slate-700">
                        <p>- Check transactions list for new partner sales.</p>
                        <p>- Verify acquisition counts per partner/date.</p>
                        <p>- Verify revenue report (transactions × guide price).</p>
                        <p>- Check partner performance graph monthly trend.</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Error Handling & Retry Rules</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm text-slate-700">
                        <p><strong>401/403:</strong> Invalid or inactive partner token.</p>
                        <p><strong>404:</strong> Product not assigned/found for partner.</p>
                        <p><strong>422:</strong> Validation error in request body.</p>
                        <p><strong>Retry:</strong> Use same `transaction_number` and same `Idempotency-Key`.</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Security Rules</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm text-slate-700">
                        <p>- Guide price is internal and never exposed to partner APIs.</p>
                        <p>- Keep token on backend only; do not expose in frontend/mobile code.</p>
                        <p>- Use HTTPS in production and rotate tokens regularly.</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Reference URLs</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3 text-sm">
                        <div className="flex items-center justify-between rounded border border-slate-200 bg-white p-3">
                            <code className="text-xs">{machineReadableGuide}</code>
                            <Button size="sm" variant="outline" onClick={() => copyToClipboard(machineReadableGuide)}>Copy</Button>
                        </div>
                        <div className="flex items-center justify-between rounded border border-slate-200 bg-white p-3">
                            <code className="text-xs">{swaggerEndpoint}</code>
                            <a href={swaggerEndpoint} target="_blank" rel="noreferrer" className="inline-flex items-center text-emerald-700 hover:underline">
                                Open <ExternalLink className="ml-1 h-4 w-4" />
                            </a>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Quick Go-Live Checklist (Partner)</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm text-slate-700">
                        <p>1. `.env` configured with correct admin base URL and partner token.</p>
                        <p>2. Verify endpoint returns success.</p>
                        <p>3. Pull products and confirm assigned product codes are visible.</p>
                        <p>4. Fetch schema and bind form fields accordingly.</p>
                        <p>5. Push test transaction with idempotency key.</p>
                        <p>6. Confirm transaction + acquisition + revenue visibility on admin portal.</p>
                    </CardContent>
                </Card>
            </div>
        </div>
    );
}