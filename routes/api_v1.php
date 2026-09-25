<?php

use App\Http\Controllers\Api\V1\Admin\PermissionController;
use App\Http\Controllers\Api\V1\Admin\RoleController as AdminRoleController;
use App\Http\Controllers\Api\V1\Admin\StatsController;
use App\Http\Controllers\Api\V1\Admin\TenantController;
use App\Http\Controllers\Api\V1\Admin\TenantUserController;
use App\Http\Controllers\Api\V1\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\Tenant\ProfileController;
use App\Http\Controllers\Api\V1\Tenant\ProjectController;
use App\Http\Controllers\Api\V1\Tenant\RoleController as TenantRoleController;
use App\Http\Controllers\Api\V1\Tenant\UserController as TenantUserControllerAlias;
use Illuminate\Support\Facades\Route;

Route::get('health', fn () => api_success([
    'status' => 'ok',
    'version' => 'v1',
], 'Service healthy.'))->name('health');

Route::prefix('auth')->name('auth.')->group(function (): void {
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:login')->name('login');
});

Route::middleware('auth:api')->group(function (): void {
    Route::prefix('auth')->name('auth.')->group(function (): void {
        Route::post('refresh', [AuthController::class, 'refresh'])->name('refresh');
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('me', [AuthController::class, 'me'])->middleware('tenant.resolve')->name('me');
    });
});

Route::prefix('admin')->name('admin.')->middleware(['auth:api', 'super.admin'])->group(function (): void {
    Route::get('stats', StatsController::class)->name('stats');

    Route::post('tenants/{tenant}/suspend', [TenantController::class, 'suspend'])->name('tenants.suspend');
    Route::post('tenants/{tenant}/activate', [TenantController::class, 'activate'])->name('tenants.activate');
    Route::apiResource('tenants', TenantController::class);

    Route::get('tenants/{tenant}/users', [TenantUserController::class, 'index'])->name('tenants.users.index');
    Route::post('tenants/{tenant}/users', [TenantUserController::class, 'store'])->name('tenants.users.store');

    Route::post('users/{user}/roles', [AdminUserController::class, 'assignRoles'])->name('users.roles');
    Route::apiResource('users', AdminUserController::class)->except(['create', 'edit']);

    Route::apiResource('roles', AdminRoleController::class)->except(['create', 'edit', 'show']);

    Route::apiResource('permissions', PermissionController::class)->only(['index', 'store']);
});

Route::prefix('tenant')->name('tenant.')->middleware(['auth:api', 'tenant.resolve', 'tenant'])->group(function (): void {
    Route::get('profile', [ProfileController::class, 'show'])->name('profile');
    Route::get('roles', [TenantRoleController::class, 'index'])->name('roles.index');

    Route::get('users', [TenantUserControllerAlias::class, 'index'])->name('users.index');
    Route::post('users', [TenantUserControllerAlias::class, 'store'])->name('users.store');
    Route::put('users/{user}', [TenantUserControllerAlias::class, 'update'])->name('users.update');
    Route::delete('users/{user}', [TenantUserControllerAlias::class, 'destroy'])->name('users.destroy');
    Route::post('users/{user}/roles', [TenantUserControllerAlias::class, 'assignRoles'])->name('users.roles');

    Route::apiResource('projects', ProjectController::class)->except(['create', 'edit']);
});
