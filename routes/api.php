<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('v1.')->group(base_path('routes/api_v1.php'));

Route::any('{any}', function () {
    return api_error('Unsupported API version.', 404, ['code' => 'unsupported_api_version']);
})->where('any', '.*');
