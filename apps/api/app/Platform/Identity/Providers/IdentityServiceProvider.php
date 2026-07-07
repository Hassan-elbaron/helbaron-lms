<?php

namespace App\Platform\Identity\Providers;

use App\Platform\Identity\Events\UserLoggedIn;
use App\Platform\Identity\Events\UserRegistered;
use App\Platform\Identity\Listeners\SendEmailOtpOnRegistration;
use App\Platform\Identity\Listeners\SendPhoneOtpOnRegistration;
use App\Platform\Identity\Listeners\UpdateLastLoginTimestamp;
use App\Platform\Identity\Models\User;
use App\Platform\Identity\Models\UserDevice;
use App\Platform\Identity\Policies\DevicePolicy;
use App\Platform\Identity\Policies\UserPolicy;
use App\Platform\Shared\Providers\BaseDomainServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Wires the Identity module: config, migrations, split route files, policies, named rate
 * limiters, and event→listener bindings. Registered in bootstrap/providers.php.
 */
class IdentityServiceProvider extends BaseDomainServiceProvider
{
    protected array $routeFiles = ['routes/auth.php', 'routes/profile.php', 'routes/devices.php'];

    protected function domainPath(): string
    {
        return dirname(__DIR__);
    }

    /** @var array<class-string, class-string> */
    protected array $policies = [
        User::class => UserPolicy::class,
        UserDevice::class => DevicePolicy::class,
    ];

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../../../config/identity.php', 'identity');
    }

    protected function bootDomain(): void
    {
        $this->registerRateLimiters();
        $this->registerListeners();
    }

    private function registerRateLimiters(): void
    {
        RateLimiter::for('identity-register', fn (Request $r) => Limit::perMinute(6)->by($r->ip()));

        // Login keyed by email + IP: one attacker can't lock every account, and one account
        // can't be brute-forced from across the network.
        RateLimiter::for('identity-login', fn (Request $r) => Limit::perMinute(10)
            ->by(strtolower((string) $r->input('email')).'|'.$r->ip()));

        RateLimiter::for('identity-password', fn (Request $r) => Limit::perMinute(6)->by($r->ip()));

        RateLimiter::for('identity-otp-verify', fn (Request $r) => Limit::perMinute(10)
            ->by(optional($r->user())->getAuthIdentifier() ?? $r->ip()));
    }

    private function registerListeners(): void
    {
        Event::listen(UserRegistered::class, SendEmailOtpOnRegistration::class);
        Event::listen(UserRegistered::class, SendPhoneOtpOnRegistration::class);
        Event::listen(UserLoggedIn::class, UpdateLastLoginTimestamp::class);
    }
}
