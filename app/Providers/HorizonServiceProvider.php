<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\SuperAdmin;
use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        if ('local' !== app()->environment()) {
            // for long waiting job notification
            Horizon::routeSlackNotificationsTo(config('horizon.slack.webhook_url'), config('horizon.slack.channel'));
        }
    }

    /**
     * Register the Horizon gate.
     *
     * This gate determines who can access Horizon in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', fn ($user): bool => SuperAdmin::class === $user::class);
    }
}
