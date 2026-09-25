<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return api_success([
        'name' => config('app.name'),
        'type' => 'REST API',
        'version' => 'v1',
        'base_url' => url('/api/v1'),
        'health' => url('/api/v1/health'),
        'documentation' => 'https://github.com/JosephVTX/laravel-multitenant-saas',
    ], 'Multi-tenant SaaS API.');
});
