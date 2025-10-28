<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use App\Models\Team;
use App\Models\AbsenceRequest;
use App\Models\PermissionRequest;
use App\Models\Badge;
use App\Policies\TeamPolicy;
use App\Policies\AbsenceRequestPolicy;
use App\Policies\PermissionRequestPolicy;
use App\Policies\BadgePolicy;
use App\Providers\EncryptedUserProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The application's policy mappings.
     *
     * @var array<string, string>
     */
    protected $policies = [
        Team::class => TeamPolicy::class,
        AbsenceRequest::class => AbsenceRequestPolicy::class,
        PermissionRequest::class => PermissionRequestPolicy::class,
        Badge::class => BadgePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // تسجيل Encrypted User Provider للبحث في البيانات المشفرة
        Auth::provider('encrypted', function ($app, array $config) {
            return new EncryptedUserProvider($app['hash'], $config['model']);
        });
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        parent::register();
    }
}
