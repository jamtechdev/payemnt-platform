import AdminLayout from '@/layouts/AdminLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Copy, Key, Code, Book } from 'lucide-react';

export default function ApiDocumentation() {
    const copyToClipboard = (text: string) => {
        navigator.clipboard.writeText(text);
    };

    const samplePayload = `{
  "partner_id": "PARTNER_001",
  "product_id": "PROD_123",
  "customer_data": {
    "first_name": "John",
    "last_name": "Doe",
    "email": "john.doe@example.com",
    "phone": "+1234567890",
    "date_of_birth": "1985-03-15",
    "cover_start_date": "2026-04-01",
    "cover_duration_months": 12,
    "custom_field_1": "value",
    "custom_field_2": 100.00
  },
  "payment": {
    "amount": 150.00,
    "currency": "USD",
    "payment_date": "2026-04-01T10:30:00Z",
    "transaction_reference": "TXN_789456"
  }
}`;

    const successResponse = `{
  "status": "success",
  "data": {
    "customer_id": "CUST_987654",
    "message": "Customer record created successfully"
  }
}`;

    const errorResponse = `{
  "status": "error",
  "error_code": "VALIDATION_ERROR",
  "message": "Invalid field: date_of_birth format must be YYYY-MM-DD",
  "details": [...]
}`;

    return (
        <AdminLayout title="API Documentation">
            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center gap-3">
                    <Book className="h-8 w-8 text-blue-600" />
                    <div>
                        <h1 className="text-2xl font-bold">API Documentation</h1>
                        <p className="text-gray-600">Integration guide for partner API access</p>
                    </div>
                </div>

                {/* Authentication */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Key className="h-5 w-5" />
                            Authentication
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div>
                            <h4 className="font-medium mb-2">API Key Authentication</h4>
                            <p className="text-sm text-gray-600 mb-3">
                                All API requests must include your API key in the Authorization header:
                            </p>
                            <div className="bg-gray-100 p-3 rounded-lg font-mono text-sm flex items-center justify-between">
                                <code>Authorization: Bearer YOUR_API_KEY</code>
                                <Button size="sm" variant="ghost" onClick={() => copyToClipboard('Authorization: Bearer YOUR_API_KEY')}>
                                    <Copy className="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                        <div className="bg-yellow-50 border border-yellow-200 p-3 rounded-lg">
                            <p className="text-sm text-yellow-800">
                                <strong>Important:</strong> Keep your API key secure and never expose it in client-side code.
                            </p>
                        </div>
                    </CardContent>
                </Card>

                {/* Endpoint Details */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Code className="h-5 w-5" />
                            Customer Submission Endpoint
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label className="text-sm font-medium text-gray-600">Method</label>
                                <Badge variant="default" className="ml-2">POST</Badge>
                            </div>
                            <div>
                                <label className="text-sm font-medium text-gray-600">Content-Type</label>
                                <code className="ml-2 text-sm">application/json</code>
                            </div>
                        </div>
                        
                        <div>
                            <label className="text-sm font-medium text-gray-600">URL</label>
                            <div className="bg-gray-100 p-3 rounded-lg font-mono text-sm flex items-center justify-between mt-1">
                                <code>{window.location.origin}/api/v1/customers</code>
                                <Button size="sm" variant="ghost" onClick={() => copyToClipboard(`${window.location.origin}/api/v1/customers`)}>
                                    <Copy className="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Request Format */}
                <Card>
                    <CardHeader>
                        <CardTitle>Request Format</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-3">
                            <p className="text-sm text-gray-600">Sample request payload:</p>
                            <div className="bg-gray-900 text-green-400 p-4 rounded-lg overflow-x-auto">
                                <div className="flex items-center justify-between mb-2">
                                    <span className="text-xs text-gray-400">JSON</span>
                                    <Button size="sm" variant="ghost" onClick={() => copyToClipboard(samplePayload)} className="text-gray-400 hover:text-white">
                                        <Copy className="h-4 w-4" />
                                    </Button>
                                </div>
                                <pre className="text-sm"><code>{samplePayload}</code></pre>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Response Format */}
                <Card>
                    <CardHeader>
                        <CardTitle>Response Format</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div>
                            <h4 className="font-medium mb-2 text-green-600">Success Response (201)</h4>
                            <div className="bg-gray-900 text-green-400 p-4 rounded-lg overflow-x-auto">
                                <div className="flex items-center justify-between mb-2">
                                    <span className="text-xs text-gray-400">JSON</span>
                                    <Button size="sm" variant="ghost" onClick={() => copyToClipboard(successResponse)} className="text-gray-400 hover:text-white">
                                        <Copy className="h-4 w-4" />
                                    </Button>
                                </div>
                                <pre className="text-sm"><code>{successResponse}</code></pre>
                            </div>
                        </div>

                        <div>
                            <h4 className="font-medium mb-2 text-red-600">Error Response (400/401/429)</h4>
                            <div className="bg-gray-900 text-red-400 p-4 rounded-lg overflow-x-auto">
                                <div className="flex items-center justify-between mb-2">
                                    <span className="text-xs text-gray-400">JSON</span>
                                    <Button size="sm" variant="ghost" onClick={() => copyToClipboard(errorResponse)} className="text-gray-400 hover:text-white">
                                        <Copy className="h-4 w-4" />
                                    </Button>
                                </div>
                                <pre className="text-sm"><code>{errorResponse}</code></pre>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Rate Limiting */}
                <Card>
                    <CardHeader>
                        <CardTitle>Rate Limiting</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-3">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="bg-blue-50 p-3 rounded-lg">
                                    <h4 className="font-medium text-blue-800">Request Limit</h4>
                                    <p className="text-sm text-blue-600">1000 requests per hour</p>
                                </div>
                                <div className="bg-orange-50 p-3 rounded-lg">
                                    <h4 className="font-medium text-orange-800">Rate Limit Headers</h4>
                                    <p className="text-sm text-orange-600">Check X-RateLimit-* headers</p>
                                </div>
                            </div>
                            <div className="bg-red-50 border border-red-200 p-3 rounded-lg">
                                <p className="text-sm text-red-800">
                                    <strong>429 Too Many Requests:</strong> You'll receive this status code if you exceed the rate limit.
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Field Validation */}
                <Card>
                    <CardHeader>
                        <CardTitle>Field Validation Rules</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b">
                                        <th className="text-left p-2">Field</th>
                                        <th className="text-left p-2">Type</th>
                                        <th className="text-left p-2">Required</th>
                                        <th className="text-left p-2">Validation</th>
                                    </tr>
                                </thead>
                                <tbody className="text-gray-600">
                                    <tr className="border-b">
                                        <td className="p-2 font-mono">partner_id</td>
                                        <td className="p-2">string</td>
                                        <td className="p-2"><Badge variant="destructive" className="text-xs">Yes</Badge></td>
                                        <td className="p-2">Must match your assigned partner ID</td>
                                    </tr>
                                    <tr className="border-b">
                                        <td className="p-2 font-mono">product_id</td>
                                        <td className="p-2">string</td>
                                        <td className="p-2"><Badge variant="destructive" className="text-xs">Yes</Badge></td>
                                        <td className="p-2">Must be an active product for your partner</td>
                                    </tr>
                                    <tr className="border-b">
                                        <td className="p-2 font-mono">email</td>
                                        <td className="p-2">string</td>
                                        <td className="p-2"><Badge variant="destructive" className="text-xs">Yes</Badge></td>
                                        <td className="p-2">Valid email format</td>
                                    </tr>
                                    <tr className="border-b">
                                        <td className="p-2 font-mono">date_of_birth</td>
                                        <td className="p-2">date</td>
                                        <td className="p-2"><Badge variant="secondary" className="text-xs">No</Badge></td>
                                        <td className="p-2">Format: YYYY-MM-DD</td>
                                    </tr>
                                    <tr className="border-b">
                                        <td className="p-2 font-mono">payment.amount</td>
                                        <td className="p-2">decimal</td>
                                        <td className="p-2"><Badge variant="destructive" className="text-xs">Yes</Badge></td>
                                        <td className="p-2">Must be greater than 0</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>

                {/* Support */}
                <Card>
                    <CardHeader>
                        <CardTitle>Support & Contact</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="bg-gray-50 p-4 rounded-lg">
                            <p className="text-sm text-gray-600 mb-2">
                                For technical support or questions about the API integration:
                            </p>
                            <ul className="text-sm text-gray-600 space-y-1">
                                <li>• Email: api-support@company.com</li>
                                <li>• Response time: Within 24 hours</li>
                                <li>• Include your partner ID in all support requests</li>
                            </ul>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AdminLayout>
    );
}