<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\DnsServerResource;
use App\Models\DnsServer;
use App\Services\PowerDNSService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DnsServerController extends Controller
{
    public function __construct(protected PowerDNSService $service) {}

    /**
     * List all DNS Servers.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = DnsServer::withCount('zones');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $servers = $query->orderBy('name')->get();

        return DnsServerResource::collection($servers);
    }

    /**
     * Get specific DNS Server details.
     */
    public function show(int $id): JsonResponse
    {
        $server = DnsServer::withCount('zones')->find($id);

        if (! $server) {
            return response()->json([
                'success' => false,
                'message' => "DNS Server with ID [{$id}] not found.",
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new DnsServerResource($server),
        ]);
    }

    /**
     * Trigger health check for specific DNS Server.
     */
    public function healthCheck(int $id): JsonResponse
    {
        $server = DnsServer::find($id);

        if (! $server) {
            return response()->json([
                'success' => false,
                'message' => "DNS Server with ID [{$id}] not found.",
            ], 404);
        }

        $result = $this->service->checkServerHealth($server);

        return response()->json([
            'success' => true,
            'message' => 'Health check executed successfully.',
            'data' => $result,
        ]);
    }
}
