<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreRecordRequest;
use App\Http\Requests\Api\V1\UpdateRecordRequest;
use App\Http\Resources\Api\V1\RecordResource;
use App\Models\PdnsDomain;
use App\Models\PdnsRecord;
use App\Services\PowerDNSService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Throwable;

class RecordController extends Controller
{
    public function __construct(protected PowerDNSService $service) {}

    /**
     * List all DNS Records for a specific zone.
     */
    public function indexByZone(Request $request, int $zoneId): JsonResponse|AnonymousResourceCollection
    {
        $user = $request->user();
        $domain = PdnsDomain::find($zoneId);

        if (! $domain) {
            return response()->json([
                'success' => false,
                'message' => "DNS Zone with ID [{$zoneId}] not found.",
            ], 404);
        }

        if ($user && $user->isCustomer() && $domain->customer_id !== $user->customer_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this zone.',
            ], 403);
        }

        $query = $domain->records();

        if ($request->filled('type')) {
            $query->where('type', strtoupper($request->type));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $records = $query->orderBy('type')->get();

        return RecordResource::collection($records);
    }

    /**
     * Create a new DNS Record in a zone.
     */
    public function storeForZone(StoreRecordRequest $request, int $zoneId): JsonResponse
    {
        $user = $request->user();
        $domain = PdnsDomain::find($zoneId);

        if (! $domain) {
            return response()->json([
                'success' => false,
                'message' => "DNS Zone with ID [{$zoneId}] not found.",
            ], 404);
        }

        if ($user && $user->isCustomer() && $domain->customer_id !== $user->customer_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this zone.',
            ], 403);
        }

        try {
            $record = $this->service->createRecord($domain, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'DNS Record created successfully.',
                'data' => new RecordResource($record->load('domain')),
            ], 201);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get specific DNS Record details.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $record = PdnsRecord::with('domain')->find($id);

        if (! $record) {
            return response()->json([
                'success' => false,
                'message' => "DNS Record with ID [{$id}] not found.",
            ], 404);
        }

        if ($user && $user->isCustomer() && $record->domain?->customer_id !== $user->customer_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this record.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => new RecordResource($record),
        ]);
    }

    /**
     * Update an existing DNS Record.
     */
    public function update(UpdateRecordRequest $request, int $id): JsonResponse
    {
        $user = $request->user();
        $record = PdnsRecord::with('domain')->find($id);

        if (! $record) {
            return response()->json([
                'success' => false,
                'message' => "DNS Record with ID [{$id}] not found.",
            ], 404);
        }

        if ($user && $user->isCustomer() && $record->domain?->customer_id !== $user->customer_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this record.',
            ], 403);
        }

        try {
            $updated = $this->service->updateRecord($record, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'DNS Record updated successfully.',
                'data' => new RecordResource($updated->load('domain')),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Delete a DNS Record.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $record = PdnsRecord::with('domain')->find($id);

        if (! $record) {
            return response()->json([
                'success' => false,
                'message' => "DNS Record with ID [{$id}] not found.",
            ], 404);
        }

        if ($user && $user->isCustomer() && $record->domain?->customer_id !== $user->customer_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this record.',
            ], 403);
        }

        if ($record->type === 'SOA') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete the SOA record of a zone.',
            ], 422);
        }

        try {
            $this->service->deleteRecord($record);

            return response()->json([
                'success' => true,
                'message' => "DNS Record [{$record->name}] deleted successfully.",
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
