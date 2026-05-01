import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AdminLayout from '@/layouts/AdminLayout';
import { Copy, ExternalLink } from 'lucide-react';

export default function ApiDocumentation() {
    const baseUrl = window.location.origin;
    const swaggerEndpoint = `${baseUrl}/api/documentation`;
    const machineGuide = `${baseUrl}/api/v1/partner/guide`;

    const copyToClipboard = (text: string) => navigator.clipboard.writeText(text);

    const authExample = `Authorization: Bearer {PARTNER_TOKEN}
Idempotency-Key: {UNIQUE_REQUEST_KEY}
Content-Type: application/json`;

    const quickEndpoints = `GET  /api/v1/partner/products
GET  /api/v1/verify-token
POST /api/v1/products/{product_code}/submit
POST /api/v1/products/{product_code}/transactions/{transaction_number}/kyc
PUT  /api/v1/products/{product_code}/transactions/{transaction_number}
POST /api/v1/products/{product_code}/transactions/{transaction_number}/cancel
POST /api/v1/products/{product_code}/transactions/{transaction_number}/callback`;

    const verifyTokenCurl = `curl -X GET "${baseUrl}/api/v1/verify-token" \\
  -H "Authorization: Bearer {PARTNER_TOKEN}" \\
  -H "Accept: application/json"`;

    const productsCurl = `curl -X GET "${baseUrl}/api/v1/partner/products" \\
  -H "Authorization: Bearer {PARTNER_TOKEN}" \\
  -H "Accept: application/json"`;

    const submitPolicyCurl = `curl -X POST "${baseUrl}/api/v1/products/INSURETECH_SWAP_PROTECT/submit" \\
  -H "Authorization: Bearer {PARTNER_TOKEN}" \\
  -H "Idempotency-Key: SWAP-TXN-1001" \\
  -H "Content-Type: application/json" \\
  -d '{
    "transaction_number": "SWAP-TXN-1001",
    "customer_name": "John Doe",
    "customer_email": "john@example.com",
    "phone": "+2348000000000",
    "cover_duration": "30_days",
    "status": "pending",
    "kyc": {
      "id_type": "national_id",
      "id_number": "A1234567"
    },
    "notes": "Sale from Swap checkout"
  }'`;

    const submitKycCurl = `curl -X POST "${baseUrl}/api/v1/products/INSURETECH_SWAP_PROTECT/transactions/SWAP-TXN-1001/kyc" \\
  -H "Authorization: Bearer {PARTNER_TOKEN}" \\
  -H "Content-Type: application/json" \\
  -d '{
    "kyc": {
      "id_type": "national_id",
      "id_number": "A1234567"
    }
  }'`;

    const updatePolicyCurl = `curl -X PUT "${baseUrl}/api/v1/products/INSURETECH_SWAP_PROTECT/transactions/SWAP-TXN-1001" \\
  -H "Authorization: Bearer {PARTNER_TOKEN}" \\
  -H "Content-Type: application/json" \\
  -d '{
    "status": "active",
    "policy_number": "POL-1001",
    "notes": "Policy activated after review"
  }'`;

    const cancelPolicyCurl = `curl -X POST "${baseUrl}/api/v1/products/INSURETECH_SWAP_PROTECT/transactions/SWAP-TXN-1001/cancel" \\
  -H "Authorization: Bearer {PARTNER_TOKEN}" \\
  -H "Content-Type: application/json"`;

    return (
        <AdminLayout title="API Documentation">
            <div className="space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Partner Integration Guide</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4 text-sm text-slate-700">
                        <p>This page is the official onboarding guide for partners (example: Swap) to integrate and push sales + KYC data.</p>
                        <div className="flex flex-wrap gap-2">
                            <a href={swaggerEndpoint} target="_blank" rel="noreferrer" className="inline-flex items-center rounded-md bg-emerald-600 px-3 py-2 text-white hover:bg-emerald-700">
                                Open Swagger UI <ExternalLink className="ml-1 h-4 w-4" />
                            </a>
                            <Button variant="outline" onClick={() => copyToClipboard(swaggerEndpoint)}>Copy Swagger URL</Button>
                            <Button variant="outline" onClick={() => copyToClipboard(machineGuide)}>Copy Machine Guide URL</Button>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Auth/Header Template</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <pre className="overflow-x-auto rounded bg-slate-900 p-4 text-xs text-green-400">{authExample}</pre>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Step-by-Step Flow</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm text-slate-700">
                        <p>1. Admin creates product from admin portal.</p>
                        <p>2. Admin creates partner (Swap) and assigns product.</p>
                        <p>3. Admin generates partner token and shares securely.</p>
                        <p>4. Partner verifies token and fetches assigned products.</p>
                        <p>5. On every sale, partner calls submit API and sends customer + KYC details.</p>
                        <p>6. Admin tracks transaction, acquisition and expected revenue internally.</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Step 1 - Verify Token</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2">
                        <pre className="overflow-x-auto rounded bg-slate-900 p-4 text-xs text-green-400">{verifyTokenCurl}</pre>
                        <Button variant="outline" onClick={() => copyToClipboard(verifyTokenCurl)}>Copy cURL</Button>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Step 2 - Fetch Assigned Products</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2">
                        <pre className="overflow-x-auto rounded bg-slate-900 p-4 text-xs text-green-400">{productsCurl}</pre>
                        <Button variant="outline" onClick={() => copyToClipboard(productsCurl)}>Copy cURL</Button>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Step 3 - Submit Policy Data</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2">
                        <pre className="overflow-x-auto rounded bg-slate-900 p-4 text-xs text-green-400">{submitPolicyCurl}</pre>
                        <Button variant="outline" onClick={() => copyToClipboard(submitPolicyCurl)}>Copy cURL</Button>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Step 4 - Submit / Update KYC</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2">
                        <pre className="overflow-x-auto rounded bg-slate-900 p-4 text-xs text-green-400">{submitKycCurl}</pre>
                        <Button variant="outline" onClick={() => copyToClipboard(submitKycCurl)}>Copy cURL</Button>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Step 5 - Update or Cancel Policy</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        <pre className="overflow-x-auto rounded bg-slate-900 p-4 text-xs text-green-400">{updatePolicyCurl}</pre>
                        <pre className="overflow-x-auto rounded bg-slate-900 p-4 text-xs text-green-400">{cancelPolicyCurl}</pre>
                        <div className="flex flex-wrap gap-2">
                            <Button variant="outline" onClick={() => copyToClipboard(updatePolicyCurl)}>Copy Update cURL</Button>
                            <Button variant="outline" onClick={() => copyToClipboard(cancelPolicyCurl)}>Copy Cancel cURL</Button>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Error Handling</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm text-slate-700">
                        <p><strong>401/403</strong>: token invalid/inactive partner</p>
                        <p><strong>404</strong>: product/transaction not found</p>
                        <p><strong>409</strong>: same idempotency key used with different payload</p>
                        <p><strong>422</strong>: payload validation failed</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Swap Circle Sync Mode (Centralized)</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm text-slate-700">
                        <p>Swap integration now uses one centralized sync strategy.</p>
                        <p>- Auto sync runs from purchase APIs (`purchase_product` and Stripe success flow).</p>
                        <p>- Manual fallback sync endpoint on Swap side: <code>POST /api/insuretech/sync-all</code></p>
                        <p>- This one endpoint runs full pipeline: connection verify, then pull products, then push purchases.</p>
                        <p>- Legacy scattered sync endpoints were removed to avoid duplicate/inconsistent sync behavior.</p>
                        <p>- Ultra low-code endpoint: <code>POST /api/insuretech/one-click-sale</code> (single call submit + kyc + sync).</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Quick Endpoint List</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <pre className="overflow-x-auto rounded bg-slate-100 p-4 text-xs text-slate-700">{quickEndpoints}</pre>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Embedded Swagger</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <iframe
                            src={swaggerEndpoint}
                            className="h-[900px] w-full rounded border border-slate-200"
                            title="Swagger UI"
                        />
                    </CardContent>
                </Card>
            </div>
        </AdminLayout>
    );
}