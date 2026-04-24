<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use OpenApi\Attributes as OA;

class CustomerController extends BaseApiController
{
    #[OA\Post(
        path: '/api/v1/customers/register',
        operationId: 'partnerCustomerStore',
        summary: 'Create or update customer by email (partner_id auto-set from API key)',
        security: [['sanctum' => []]],
        tags: ['Customer'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['first_name', 'last_name', 'email', 'password', 'status'],
                properties: [
                    new OA\Property(property: 'company_name', type: 'string', example: 'Acme Corp'),
                    new OA\Property(property: 'first_name', type: 'string', example: 'John'),
                    new OA\Property(property: 'last_name', type: 'string', example: 'Doe'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'secret123'),
                    new OA\Property(property: 'phone', type: 'string', example: '+923001234567'),
                    new OA\Property(property: 'location', type: 'string', example: 'Karachi'),
                    new OA\Property(property: 'valid_document', type: 'string', example: 'CNIC-12345'),
                    new OA\Property(property: 'id_front_image', type: 'string', example: 'https://example.com/id_front.jpg'),
                    new OA\Property(property: 'id_back_image', type: 'string', example: 'https://example.com/id_back.jpg'),
                    new OA\Property(property: 'profile_pic', type: 'string', example: 'https://example.com/profile.jpg'),
                    new OA\Property(property: 'status', type: 'string', enum: ['Pending', 'Active', 'Inactive', 'Deleted']),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Customer created or updated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $partner = $request->attributes->get('partner');

        $validated = $request->validate([
            'company_name'   => ['nullable', 'string', 'max:255'],
            'first_name'     => ['required', 'string', 'max:255'],
            'last_name'      => ['required', 'string', 'max:255'],
            'email'          => ['required', 'email'],
            'password'       => ['required', 'string', 'min:6'],
            'phone'          => ['nullable', 'string', 'max:20'],
            'location'       => ['nullable', 'string', 'max:255'],
            'valid_document' => ['nullable', 'string', 'max:255'],
            'id_front_image' => ['nullable', 'string', 'max:500'],
            'id_back_image'  => ['nullable', 'string', 'max:500'],
            'profile_pic'    => ['nullable', 'string', 'max:500'],
            'status'         => ['required', 'in:Pending,Active,Inactive,Deleted'],
        ]);

        $result = DB::transaction(function () use ($validated, $partner) {
            $user = User::updateOrCreate(
                ['email' => $validated['email']],
                [
                    'name'      => $validated['first_name'].' '.$validated['last_name'],
                    'phone'     => $validated['phone'] ?? null,
                    'password'  => Hash::make($validated['password']),
                    'status'    => 'active',
                    'is_active' => true,
                ]
            );

            $customer = Customer::updateOrCreate(
                [
                    'email'      => $validated['email'],
                    'partner_id' => $partner->id,
                ],
                [
                    'platform_user_id' => $user->id,
                    'company_name'     => $validated['company_name'] ?? null,
                    'first_name'       => $validated['first_name'],
                    'last_name'        => $validated['last_name'],
                    'phone'            => $validated['phone'] ?? null,
                    'location'         => $validated['location'] ?? null,
                    'valid_document'   => $validated['valid_document'] ?? null,
                    'id_front_image'   => $validated['id_front_image'] ?? null,
                    'id_back_image'    => $validated['id_back_image'] ?? null,
                    'profile_pic'      => $validated['profile_pic'] ?? null,
                    'status'           => $validated['status'],
                ]
            );

            return ['user' => $user, 'customer' => $customer];
        });

        return $this->success([
            'user'     => $result['user'],
            'customer' => $result['customer'],
        ], 200);
    }

    #[OA\Put(
        path: '/api/v1/customers/{customer_code}',
        operationId: 'partnerCustomerUpdate',
        summary: 'Update customer by customer_code',
        security: [['sanctum' => []]],
        tags: ['Customer'],
        parameters: [new OA\Parameter(name: 'customer_code', in: 'path', required: true, schema: new OA\Schema(type: 'string'), example: 'CUST_00000001')],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'company_name', type: 'string', example: 'Acme Corp'),
                    new OA\Property(property: 'first_name', type: 'string', example: 'John'),
                    new OA\Property(property: 'last_name', type: 'string', example: 'Doe'),
                    new OA\Property(property: 'phone', type: 'string', example: '+923001234567'),
                    new OA\Property(property: 'location', type: 'string', example: 'Karachi'),
                    new OA\Property(property: 'valid_document', type: 'string', example: 'CNIC-12345'),
                    new OA\Property(property: 'id_front_image', type: 'string', example: 'https://example.com/id_front.jpg'),
                    new OA\Property(property: 'id_back_image', type: 'string', example: 'https://example.com/id_back.jpg'),
                    new OA\Property(property: 'profile_pic', type: 'string', example: 'https://example.com/profile.jpg'),
                    new OA\Property(property: 'status', type: 'string', enum: ['Pending', 'Active', 'Inactive', 'Deleted'], example: 'Active'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Updated'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(Request $request, string $customer_code): JsonResponse
    {
        $partner = $request->attributes->get('partner');

        $customer = Customer::query()
            ->where('customer_code', $customer_code)
            ->where('partner_id', $partner->id)
            ->first();

        if (! $customer) {
            return $this->error('NOT_FOUND', 'Customer not found', [], 404);
        }

        $validated = $request->validate([
            'company_name'   => ['sometimes', 'nullable', 'string', 'max:255'],
            'first_name'     => ['sometimes', 'string', 'max:255'],
            'last_name'      => ['sometimes', 'string', 'max:255'],
            'phone'          => ['sometimes', 'nullable', 'string', 'max:20'],
            'location'       => ['sometimes', 'nullable', 'string', 'max:255'],
            'valid_document' => ['sometimes', 'nullable', 'string', 'max:255'],
            'id_front_image' => ['sometimes', 'nullable', 'string', 'max:500'],
            'id_back_image'  => ['sometimes', 'nullable', 'string', 'max:500'],
            'profile_pic'    => ['sometimes', 'nullable', 'string', 'max:500'],
            'status'         => ['sometimes', 'in:Pending,Active,Inactive,Deleted'],
        ]);

        DB::transaction(function () use ($customer, $validated): void {
            $customer->update($validated);

            if ($customer->user && (isset($validated['first_name']) || isset($validated['last_name']))) {
                $customer->user->update([
                    'name' => ($validated['first_name'] ?? $customer->first_name).' '.($validated['last_name'] ?? $customer->last_name),
                ]);
            }
        });

        return $this->success($customer->fresh());
    }

    #[OA\Delete(
        path: '/api/v1/customers',
        operationId: 'partnerCustomerDestroy',
        summary: 'Permanently delete all customers of authenticated partner',
        security: [['sanctum' => []]],
        tags: ['Customer'],
        responses: [
            new OA\Response(response: 200, description: 'Customers permanently deleted'),
            new OA\Response(response: 404, description: 'No customers found'),
        ]
    )]
    public function destroy(Request $request): JsonResponse
    {
        $partner = $request->attributes->get('partner');

        $deleted = Customer::withTrashed()
            ->where('partner_id', $partner->id)
            ->forceDelete();

        if ($deleted === 0) {
            return $this->error('NOT_FOUND', 'No customers found for this partner.', [], 404);
        }

        return $this->success(['deleted_count' => $deleted]);
    }
}
