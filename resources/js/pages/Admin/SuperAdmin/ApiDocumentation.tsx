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

    const quickEndpoints = `GET  /api/v1/verify-token
GET  /api/v1/products/{product_code}/fields
POST /api/v1/products/{product_code}/submit
POST /api/v1/products/{product_code}/transactions/{transaction_number}/kyc
PUT  /api/v1/products/{product_code}/transactions/{transaction_number}
POST /api/v1/products/{product_code}/transactions/{transaction_number}/cancel
POST /api/v1/products/{product_code}/transactions/{transaction_number}/callback`;

    return (
        <AdminLayout title="API Documentation">
            <div className="space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Partner API Docs (Swagger)</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4 text-sm text-slate-700">
                        <p>Share this page or direct Swagger URL with any integration partner for quick setup.</p>
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