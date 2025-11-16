<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Attendance;
use App\Models\Student;
use App\Models\User;
use App\Policies\AttendancePolicy;
use App\Policies\StudentPolicy;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Student::class, StudentPolicy::class);
        Gate::policy(Attendance::class, AttendancePolicy::class);

        $this->registerMonitoringGates();

        Scramble::afterOpenApiGenerated(static function (OpenApi $openApi): void {
            $openApi->secure(SecurityScheme::http('bearer'));
        });

        DB::prohibitDestructiveCommands(
            $this->app->environment('production')
        );
    }

    /**
     * Register gates for observability tooling and docs access.
     */
    private function registerMonitoringGates(): void
    {
        Gate::define('viewApiDocs', function (?User $user = null): bool {
            return $this->app->environment('local')
                || ($user instanceof User && $this->isAdmin($user));
        });

        Gate::define('viewPulse', function (?User $user = null): bool {
            return $this->app->environment('local')
                || ($user instanceof User && $this->isAdmin($user));
        });
    }

    /**
     * Determine if the provided user holds admin privileges.
     */
    private function isAdmin(User $user): bool
    {
        return $user->isAdmin();
    }
}
