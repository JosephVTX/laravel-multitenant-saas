<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

abstract class ApiController extends Controller
{
    use AuthorizesRequests;

    /**
     * Resolve the requested page size, always clamped to a safe maximum.
     */
    protected function perPage(Request $request): int
    {
        $perPage = $request->integer('per_page', (int) config('tenancy.pagination.default', 15));

        return max(1, min($perPage, (int) config('tenancy.pagination.max', 100)));
    }

    /**
     * Return a paginated resource collection using the standard envelope.
     */
    protected function paginated(ResourceCollection $collection, string $message = 'OK'): JsonResponse
    {
        $payload = $collection->response()->getData(true);

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $payload['data'] ?? [],
            'meta' => $payload['meta'] ?? [],
            'links' => $payload['links'] ?? [],
        ]);
    }
}
