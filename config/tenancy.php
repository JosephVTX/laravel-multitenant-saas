<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Central Domain
    |--------------------------------------------------------------------------
    |
    | The single public host that serves every tenant. This boilerplate does
    | not use wildcard subdomains: tenants are resolved from the authenticated
    | user (or explicitly, by a super admin) on this single domain.
    |
    */

    'central_domain' => env('TENANT_CENTRAL_DOMAIN', 'saas.ndn.pe'),

    /*
    |--------------------------------------------------------------------------
    | Tenant Resolution Headers
    |--------------------------------------------------------------------------
    |
    | Super administrators may act on behalf of a tenant by sending one of
    | these headers. Regular tenant users can never override their own tenant.
    |
    */

    'header' => env('TENANT_HEADER', 'X-Tenant'),

    /*
    |--------------------------------------------------------------------------
    | Resolution Cache
    |--------------------------------------------------------------------------
    |
    | Tenant lookups happen on every request, so resolved tenants are cached
    | (Redis by default) to keep the critical path free of database queries.
    |
    */

    'cache' => [
        'store' => env('TENANT_CACHE_STORE'),
        'ttl' => (int) env('TENANT_CACHE_TTL', 3600),
        'prefix' => env('TENANT_CACHE_PREFIX', 'tenant'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    |
    | Global pagination guards for every list endpoint. Client supplied
    | "per_page" values are always clamped to this upper bound.
    |
    */

    'pagination' => [
        'default' => (int) env('TENANT_PAGINATION_DEFAULT', 15),
        'max' => (int) env('TENANT_PAGINATION_MAX', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Requests are limited per authenticated user (or IP when guest). Tenant
    | users get a higher budget than anonymous traffic on the critical path.
    |
    */

    'rate_limit' => [
        'tenant' => (int) env('TENANT_RATE_LIMIT', 300),
        'guest' => (int) env('GUEST_RATE_LIMIT', 60),
    ],

];
