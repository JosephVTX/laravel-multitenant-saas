<?php

namespace App\Providers;

use App\Models\User;
use App\Support\Tenancy\TenantContext;
use App\Support\Tenancy\TenantResolver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Octane\Events\RequestTerminated;
use Spatie\Permission\PermissionRegistrar;

final class TenancyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(TenantContext::class);

        $this->app->singleton(TenantResolver::class, function ($app): TenantResolver {
            return new TenantResolver($app['cache']->store(config('tenancy.cache.store')));
        });
    }

    public function boot(): void
    {
        $this->configureModels();
        $this->configureAuthorization();
        $this->configureRateLimiting();
        $this->configureOctane();
    }

    private function configureModels(): void
    {
        Model::preventLazyLoading(! app()->isProduction());
        Model::preventSilentlyDiscardingAttributes(! app()->isProduction());
    }

    private function configureAuthorization(): void
    {
        Gate::before(function (User $user, string $ability): ?bool {
            return $user->isSuperAdmin() ? true : null;
        });
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request): Limit {
            $user = $request->user();

            $perMinute = $user?->tenant_id
                ? (int) config('tenancy.rate_limit.tenant', 300)
                : (int) config('tenancy.rate_limit.guest', 60);

            return Limit::perMinute($perMinute)->by($user?->getAuthIdentifier() ?: $request->ip());
        });

        RateLimiter::for('login', function (Request $request): Limit {
            return Limit::perMinute(10)->by($request->input('email').'|'.$request->ip());
        });
    }

    /**
     * Long running workers keep singletons alive across requests, so request
     * scoped state must be reset explicitly between requests.
     */
    private function configureOctane(): void
    {
        if (! class_exists(RequestTerminated::class)) {
            return;
        }

        Event::listen(RequestTerminated::class, function (): void {
            app(TenantContext::class)->clear();
            app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        });
    }
}
