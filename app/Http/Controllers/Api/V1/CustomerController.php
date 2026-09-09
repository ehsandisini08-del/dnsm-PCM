<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CustomerController extends Controller
{
    /**
     * List all Customers.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Customer::withCount('zones');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('company', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $customers = $query->orderBy('name')->paginate($perPage);

        return CustomerResource::collection($customers);
    }

    /**
     * Get specific Customer details.
     */
    public function show(int $id): JsonResponse
    {
        $customer = Customer::with(['zones'])->withCount('zones')->find($id);

        if (! $customer) {
            return response()->json([
                'success' => false,
                'message' => "Customer with ID [{$id}] not found.",
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new CustomerResource($customer),
        ]);
    }
}
