<?php

namespace App\Providers;

use App\Models\Role;
use App\Models\User;
use App\Policies\RolePolicy;
use App\Policies\UserPolicy;
use App\Search\EloquentSearchEngine;
use App\Search\SearchEngine;
use App\Support\SiteSettings;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(SearchEngine::class, EloquentSearchEngine::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(fn (User $user) => $user->hasRole('super-admin') ? true : null);
        Gate::define('permission', fn (User $user, string $permission) => $user->hasPermission($permission));
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);

        $siteSettings = app(SiteSettings::class);
        View::share('siteSettings', $siteSettings);
        config(['app.name' => $siteSettings->siteName()]);

        // Ensures every generated URL (route(), asset(), the sitemap, OG
        // tags...) is https:// in production, even if the app itself is
        // reached over plain HTTP from a reverse proxy that terminates TLS.
        // The actual HTTP -> HTTPS redirect and certificate belong at the
        // web server / load balancer, not here — see README.md.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
