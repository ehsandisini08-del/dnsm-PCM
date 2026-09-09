<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreZoneRequest;
use App\Http\Requests\Api\V1\UpdateZoneRequest;
use App\Http\Resources\Api\V1\ZoneResource;
use App\Models\PdnsDomain;
use App\Services\PowerDNSService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Throwable;

class ZoneController extends Controller
{
    public function __construct(protected PowerDNSService $service) {}

    /**
     * List all DNS Zones (scoped for customer if authenticated as customer).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        $query = PdnsDomain::with(['customer', 'dnsServer'])->withCount('records');

        // Customer scoping
        if ($user && $user->isCustomer()) {
            $query->where('customer_id', $user->customer_id);
        } elseif ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->filled('type')) {
            $query->where('type', strtoupper($request->type));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('dns_server_id')) {
            $query->where('dns_server_id', $request->dns_server_id);
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $zones = $query->orderBy('name')->paginate($perPage);

        return ZoneResource::collection($zones);
    }

    /**
     * Create a new DNS Zone.
     */
    public function store(StoreZoneRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        if ($user && $user->isCustomer()) {
            $data['customer_id'] = $user->customer_id;
        }

        try {
            $domain = $this->service->createZone($data);

            return response()->json([
                'success' => true,
                'message' => 'DNS Zone created successfully.',
                'data' => new ZoneResource($domain->load(['records', 'customer', 'dnsServer'])),
            ], 201);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get specific DNS Zone details.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $domain = PdnsDomain::with(['records', 'customer', 'dnsServer'])->find($id);

        if (! $domain) {
            return response()->json([
                'success' => false,
                'message' => "DNS Zone with ID [{$id}] not found.",
            ], 404);
        }

        if ($user && $user->isCustomer() && $domain->customer_id !== $user->customer_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this zone.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => new ZoneResource($domain),
        ]);
    }

    /**
     * Update DNS Zone attributes.
     */
    public function update(UpdateZoneRequest $request, int $id): JsonResponse
    {
        $user = $request->user();
        $domain = PdnsDomain::find($id);

        if (! $domain) {
            return response()->json([
                'success' => false,
                'message' => "DNS Zone with ID [{$id}] not found.",
            ], 404);
        }

        if ($user && $user->isCustomer() && $domain->customer_id !== $user->customer_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this zone.',
            ], 403);
        }

        try {
            $updated = $this->service->updateZone($domain, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'DNS Zone updated successfully.',
                'data' => new ZoneResource($updated->load(['customer', 'dnsServer'])),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Delete a DNS Zone.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $domain = PdnsDomain::find($id);

        if (! $domain) {
            return response()->json([
                'success' => false,
                'message' => "DNS Zone with ID [{$id}] not found.",
            ], 404);
        }

        if ($user && ($user->isOperator() || ($user->isCustomer() && $domain->customer_id !== $user->customer_id))) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete this zone.',
            ], 403);
        }

        try {
            $this->service->deleteZone($domain);

            return response()->json([
                'success' => true,
                'message' => "DNS Zone [{$domain->name}] deleted successfully.",
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Synchronize a DNS Zone.
     */
    public function sync(Request $request, int $id): JsonResponse
    {
        $domain = PdnsDomain::find($id);

        if (! $domain) {
            return response()->json([
                'success' => false,
                'message' => "DNS Zone with ID [{$id}] not found.",
            ], 404);
        }

        $synced = $this->service->syncZone($domain);

        return response()->json([
            'success' => $synced,
            'message' => $synced ? 'Zone synchronized successfully.' : 'Zone synchronization failed.',
            'data' => [
                'sync_status' => $domain->fresh()->sync_status,
                'sync_error' => $domain->fresh()->sync_error,
            ],
        ], $synced ? 200 : 422);
    }
}
