<?php

use Illuminate\Http\JsonResponse;

if (! function_exists('api_success')) {
    /**
     * Build a consistent success JSON response.
     *
     * @param  array<string, mixed>  $meta
     */
    function api_success(mixed $data = null, string $message = 'OK', int $status = 200, array $meta = []): JsonResponse
    {
        $payload = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $payload['data'] = $data;
        }

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }
}

if (! function_exists('api_error')) {
    /**
     * Build a consistent error JSON response.
     *
     * @param  array<string, mixed>  $extra
     */
    function api_error(string $message = 'Error', int $status = 400, array $extra = []): JsonResponse
    {
        return response()->json(array_merge([
            'success' => false,
            'message' => $message,
        ], $extra), $status);
    }
}
