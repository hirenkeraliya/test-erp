<?php

declare(strict_types=1);

namespace App\Providers;

use App\CommonFunctions;
use App\Domains\Common\Enums\ModelMapping;
use App\Pulse\UserResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Pulse\Users;
use Opcodes\LogViewer\Facades\LogViewer;
use Spatie\Health\Checks\Checks\CacheCheck;
use Spatie\Health\Checks\Checks\DatabaseCheck;
use Spatie\Health\Checks\Checks\DatabaseConnectionCountCheck;
use Spatie\Health\Checks\Checks\DatabaseSizeCheck;
use Spatie\Health\Checks\Checks\DebugModeCheck;
use Spatie\Health\Checks\Checks\HorizonCheck;
use Spatie\Health\Checks\Checks\OptimizedAppCheck;
use Spatie\Health\Checks\Checks\QueueCheck;
use Spatie\Health\Checks\Checks\RedisCheck;
use Spatie\Health\Checks\Checks\RedisMemoryUsageCheck;
use Spatie\Health\Checks\Checks\UsedDiskSpaceCheck;
use Spatie\Health\Facades\Health;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(Request $request): void
    {
        $this->app->bind(Users::class, UserResolver::class);

        if ('local' !== app()->environment()) {
            Queue::failing(function (JobFailed $event): void {
                Log::channel('job_failure_slack')->error($event->exception->getMessage(), [
                    'Job' => $event->job->resolveName(),
                    'URL' => config('app.url'),
                ]);
            });
        }

        Health::checks([
            CacheCheck::new(),
            OptimizedAppCheck::new(),
            DatabaseCheck::new(),
            DatabaseConnectionCountCheck::new()
                ->warnWhenMoreConnectionsThan(intval(config('app.warn_connection_count')))
                ->failWhenMoreConnectionsThan(intval(config('app.fail_connection_count'))),
            DatabaseSizeCheck::new()->failWhenSizeAboveGb(errorThresholdGb: intval(config('app.database_size_check'))),
            DebugModeCheck::new(),
            HorizonCheck::new(),
            QueueCheck::new(),
            RedisCheck::new(),
            RedisMemoryUsageCheck::new()->failWhenAboveMb(floatval(config('app.redis_memory_usage_check'))),
            UsedDiskSpaceCheck::new()
                ->warnWhenUsedSpaceIsAbovePercentage(70)
                ->failWhenUsedSpaceIsAbovePercentage(90),
        ]);

        Blade::directive(
            'currencyFormat',
            fn (float|string $amount): string => sprintf('<?php echo number_format(%s, 2, ".", ","); ?>', $amount)
        );

        Blade::directive(
            'truncateDecimal',
            fn (float|string $amount): string => sprintf('<?php echo round(%s, 2); ?>', $amount)
        );

        Blade::directive(
            'printNestedAttributes',
            fn ($expression): string => sprintf(
                '<?php echo ' . CommonFunctions::class . '::printNestedAttributes(%s); ?>',
                $expression
            )
        );

        Relation::enforceMorphMap(ModelMapping::getFormattedArray());
        // TODO: Removing database query logging after 15 seconds
        // This is a temporary solution to avoid logging long queries in production.
        // DB::listen(function ($query): void {
        //     if ($query->time > 15000) {
        //         Log::warning('Database query exceeded 15 seconds.', [
        //             'sql' => $query->sql,
        //         ]);
        //     }
        // });

        LogViewer::auth(fn ($request): bool => $request->user()
            && $request->user()::class === ModelMapping::SUPER_ADMIN->value);

        Model::shouldBeStrict();

        if ('local' === app()->environment()) {
            return;
        }

        if ('testing' === app()->environment()) {
            return;
        }

        Password::defaults(fn (): Password => Password::min(8)->mixedCase()->numbers()->symbols());

        Model::handleLazyLoadingViolationUsing(function ($model, $relation) use ($request): void {
            $class = $model::class;

            $currenRouteName = $request->path();

            Log::error(
                sprintf('Attempted to lazy load [%s] on model [%s] from [%s] ', $relation, $class, $currenRouteName)
            );
        });

        Model::handleDiscardedAttributeViolationUsing(function ($model, $relation) use ($request): void {
            $class = $model::class;

            $currenRouteName = $request->path();

            Log::error(
                sprintf(
                    'Add fillable property [%s] to allow mass assignment on [%s] from [%s] ',
                    implode(',', $relation),
                    $class,
                    $currenRouteName
                )
            );
        });

        Model::handleMissingAttributeViolationUsing(function ($model, $relation) use ($request): void {
            $class = $model::class;

            $currenRouteName = $request->path();

            Log::error(
                sprintf(
                    'The attribute [%s] either does not exist or was not retrieved for model [%s] from [%s] ',
                    $relation,
                    $class,
                    $currenRouteName
                )
            );
        });
    }
}
