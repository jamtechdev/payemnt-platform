import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AdminLayout from '@/layouts/AdminLayout';
import { Button } from '@/components/ui/button';
import { Copy, ExternalLink, ArrowRight, ArrowLeft } from 'lucide-react';

const copyText = (text: string) => navigator.clipboard.writeText(text);

function CodeBlock({ code }: { code: string }) {
    return (
        <div className="relative">
            <pre className="overflow-x-auto rounded-lg bg-slate-900 p-4 text-xs leading-relaxed text-green-400">{code}</pre>
            <button
                onClick={() => copyText(code)}
                className="absolute right-2 top-2 rounded p-1 text-slate-400 hover:text-white"
                title="Copy"
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

    return (
        <AdminLayout title="API Guide">
            <div className="space-y-6">

                {/* Header */}
                <Card className="border-primary/20 bg-primary/5">
                    <CardContent className="pt-5">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h2 className="text-lg font-semibold text-slate-800">Swap Circle ↔ Insurtech Integration Guide</h2>
                                <p className="text-sm text-slate-500">How Swap Circle connects to this platform, pulls products and pushes transactions.</p>
                            </div>
                            <div className="flex gap-2">
                                <a href={swaggerUrl} target="_blank" rel="noreferrer" className="inline-flex items-center gap-1 rounded-md bg-emerald-600 px-3 py-2 text-sm text-white hover:bg-emerald-700">
                                    Open Swagger <ExternalLink className="h-3.5 w-3.5" />
                                </a>
                                <Button variant="outline" size="sm" onClick={() => copyText(swaggerUrl)}>Copy Swagger URL</Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Step 1 - Configuration */}
                <Card>
                    <CardHeader>
                        <SectionTitle n="1" title="Configuration — System Settings (Swap Circle Side)" sub="Swap Circle apne admin panel → System Settings mein yeh 3 values set karta hai" />
                    </CardHeader>
                    <CardContent className="space-y-3">
                        <div className="overflow-auto rounded-lg border border-border">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/40 text-left text-xs text-muted-foreground">
                                        <th className="px-4 py-2 font-medium">ENV Key</th>
                                        <th className="px-4 py-2 font-medium">Value</th>
                                        <th className="px-4 py-2 font-medium">Description</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border/60">
                                    <tr>
                                        <td className="px-4 py-2 font-mono text-xs text-primary">INSURETECH_ADMIN_BASE_URL</td>
                                        <td className="px-4 py-2 text-xs text-slate-600">{baseUrl}</td>
                                        <td className="px-4 py-2 text-xs text-slate-500">This Insurtech portal ka URL</td>
                                    </tr>
                                    <tr>
                                        <td className="px-4 py-2 font-mono text-xs text-primary">INSURETECH_PARTNER_TOKEN</td>
                                        <td className="px-4 py-2 text-xs text-slate-600">Bearer token from admin portal</td>
                                        <td className="px-4 py-2 text-xs text-slate-500">Partners → Generate API Key se milta hai</td>
                                    </tr>
                                    <tr>
                                        <td className="px-4 py-2 font-mono text-xs text-primary">INSURETECH_REQUEST_TIMEOUT</td>
                                        <td className="px-4 py-2 text-xs text-slate-600">30</td>
                                        <td className="px-4 py-2 text-xs text-slate-500">Request timeout in seconds</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p className="text-xs text-slate-500">
                            ⚠️ API key admin portal se generate hoti hai: <strong>Partners → Partner Detail → Generate API Key</strong>. Ek baar hi dikhti hai — copy karke Swap Circle ke system settings mein save karo.
                        </p>
                    </CardContent>
                </Card>

                {/* Step 2 - Pull Products */}
                <Card>
                    <CardHeader>
                        <SectionTitle
                            n="2"
                            title="Pull Products — Insurtech → Swap Circle"
                            sub='Swap Circle "Pull Admin Products" button dabata hai — hamare platform se products fetch hoti hain'
                        />
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="flex items-center gap-2 text-sm">
                            <span className="rounded bg-slate-100 px-2 py-1 font-mono text-xs">Swap Circle</span>
                            <ArrowRight className="h-4 w-4 text-slate-400" />
                            <span className="rounded bg-emerald-50 px-2 py-1 font-mono text-xs text-emerald-700">POST /api/insuretech/pull-products</span>
                            <ArrowRight className="h-4 w-4 text-slate-400" />
                            <span className="rounded bg-blue-50 px-2 py-1 font-mono text-xs text-blue-700">Insurtech API</span>
                        </div>

                        <div>
                            <p className="mb-1 text-xs font-medium text-slate-600">Swap Circle internally calls this Insurtech endpoint:</p>
                            <CodeBlock code={`GET ${baseUrl}/api/v1/partner/products\nAuthorization: Bearer {INSURETECH_PARTNER_TOKEN}`} />
                        </div>

                        <div>
                            <p className="mb-1 text-xs font-medium text-slate-600">Expected response from Insurtech:</p>
                            <CodeBlock code={`{
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
}`} />
                        </div>

                        <p className="text-xs text-slate-500">
                            Swap Circle yeh products apni <code className="rounded bg-slate-100 px-1">products</code> table mein save karta hai aur <code className="rounded bg-slate-100 px-1">it_product_mappings</code> mein mapping store karta hai.
                        </p>
                    </CardContent>
                </Card>

                {/* Step 3 - Push Transaction */}
                <Card>
                    <CardHeader>
                        <SectionTitle
                            n="3"
                            title="Push Transaction — Swap Circle → Insurtech"
                            sub="Jab user Swap Circle pe product purchase karta hai, transaction automatically yahan push hoti hai"
                        />
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="flex items-center gap-2 text-sm">
                            <span className="rounded bg-slate-100 px-2 py-1 font-mono text-xs">Swap Circle</span>
                            <ArrowRight className="h-4 w-4 text-slate-400" />
                            <span className="rounded bg-blue-50 px-2 py-1 font-mono text-xs text-blue-700">POST {baseUrl}/api/v1/transactions</span>
                            <ArrowLeft className="h-4 w-4 text-slate-400" />
                            <span className="rounded bg-emerald-50 px-2 py-1 font-mono text-xs text-emerald-700">Insurtech receives</span>
                        </div>

                        <div>
                            <p className="mb-1 text-xs font-medium text-slate-600">Swap Circle calls this endpoint on every purchase:</p>
                            <CodeBlock code={`POST ${baseUrl}/api/v1/transactions\nAuthorization: Bearer {INSURETECH_PARTNER_TOKEN}\nIdempotency-Key: {transaction_number}\nContent-Type: application/json`} />
                        </div>

                        <div>
                            <p className="mb-1 text-xs font-medium text-slate-600">Request body:</p>
                            <CodeBlock code={`{
  "transaction_number": "SWAP-1234-5678",
  "customer_email":     "john@example.com",
  "product_code":       "NIGERIA_BENEFICIARY_COMMUNITY",
  "cover_duration":     "Monthly",
  "payment_status":     "Successful",
  "notes":              "Synced from swap-circle",
  "date_added":         "2026-05-01 10:00:00"
}`} />
                        </div>
                    </CardContent>
                </Card>

                {/* Step 4 - Connection Test */}
                <Card>
                    <CardHeader>
                        <SectionTitle
                            n="4"
                            title="Connection Test"
                            sub="Swap Circle connection verify karne ke liye yeh endpoint call karta hai"
                        />
                    </CardHeader>
                    <CardContent className="space-y-3">
                        <CodeBlock code={`GET /api/insuretech/test-connection   ← Swap Circle ka internal endpoint\n\n# Internally calls:\nGET ${baseUrl}/api/v1/partner/products\nAuthorization: Bearer {INSURETECH_PARTNER_TOKEN}\n\n# Agar 200 aaye → Connected ✓\n# Agar 401/500 aaye → Not Connected ✗`} />
                    </CardContent>
                </Card>

                {/* Summary Table */}
                <Card>
                    <CardHeader><CardTitle className="text-base">Summary</CardTitle></CardHeader>
                    <CardContent>
                        <div className="overflow-auto rounded-lg border border-border">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/40 text-left text-xs text-muted-foreground">
                                        <th className="px-4 py-2 font-medium">Action</th>
                                        <th className="px-4 py-2 font-medium">Swap Circle Endpoint</th>
                                        <th className="px-4 py-2 font-medium">Insurtech Endpoint</th>
                                        <th className="px-4 py-2 font-medium">Direction</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border/60">
                                    <tr className="hover:bg-muted/30">
                                        <td className="px-4 py-2 text-xs font-medium">Pull Products</td>
                                        <td className="px-4 py-2 font-mono text-xs text-slate-600">POST /api/insuretech/pull-products</td>
                                        <td className="px-4 py-2 font-mono text-xs text-blue-700">GET /api/v1/partner/products</td>
                                        <td className="px-4 py-2 text-xs text-emerald-600">Insurtech → Swap</td>
                                    </tr>
                                    <tr className="hover:bg-muted/30">
                                        <td className="px-4 py-2 text-xs font-medium">Push Transaction</td>
                                        <td className="px-4 py-2 font-mono text-xs text-slate-600">Auto on purchase</td>
                                        <td className="px-4 py-2 font-mono text-xs text-blue-700">POST /api/v1/transactions</td>
                                        <td className="px-4 py-2 text-xs text-blue-600">Swap → Insurtech</td>
                                    </tr>
                                    <tr className="hover:bg-muted/30">
                                        <td className="px-4 py-2 text-xs font-medium">Test Connection</td>
                                        <td className="px-4 py-2 font-mono text-xs text-slate-600">GET /api/insuretech/test-connection</td>
                                        <td className="px-4 py-2 font-mono text-xs text-blue-700">GET /api/v1/partner/products</td>
                                        <td className="px-4 py-2 text-xs text-emerald-600">Swap → Insurtech</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div className="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3">
                            <p className="text-xs font-medium text-amber-800">Insurtech portal ko sirf 2 APIs provide karni hain:</p>
                            <ul className="mt-1 space-y-1 text-xs text-amber-700">
                                <li>• <code className="rounded bg-amber-100 px-1">GET /api/v1/partner/products</code> — partner ki active products list return kare</li>
                                <li>• <code className="rounded bg-amber-100 px-1">POST /api/v1/transactions</code> — Swap Circle se aane wali transaction receive kare</li>
                                <li>• Dono pe <code className="rounded bg-amber-100 px-1">Authorization: Bearer token</code> validate hoga</li>
                            </ul>
                        </div>
                    </CardContent>
                </Card>

                {/* Embedded Swagger */}
                <Card>
                    <CardHeader><CardTitle className="text-base">Live API Reference (Swagger)</CardTitle></CardHeader>
                    <CardContent>
                        <iframe src={swaggerUrl} className="h-[900px] w-full rounded border border-slate-200" title="Swagger UI" />
                    </CardContent>
                </Card>

            </div>
        </AdminLayout>
    );
}
