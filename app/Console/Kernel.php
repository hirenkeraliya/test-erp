<?php

declare(strict_types=1);

namespace App\Console;

use App\Console\Commands\DispatchJobs;
use App\Domains\AutomatedNotification\Jobs\AutomatedNotificationJob;
use App\Domains\Azentio\Jobs\SyncAzentioItemsJob;
use App\Domains\Azentio\Jobs\SyncAzentioMembersJob;
use App\Domains\CategoryWiseDailyTotal\Jobs\DailySalesUpdateJob;
use App\Domains\CategoryWiseDailyTotal\Jobs\DailyTopTenStoreSalesJob;
use App\Domains\Counter\Jobs\UpdateCompanyCounterDetailsJob;
use App\Domains\CreditNote\Jobs\CreditNoteExpirationJob;
use App\Domains\CurrencyRate\Jobs\CurrencyRateUpdateJob;
use App\Domains\DreamPrice\Jobs\DreamPriceOverlayRestrictionJob;
use App\Domains\GiftCard\Jobs\GiftCardExpirationJob;
use App\Domains\LoyaltyPointUpdate\Jobs\LoyaltyPointExpirationJob;
use App\Domains\Notification\Jobs\DeleteOldNotificationsJob;
use App\Domains\PosAdmin\Jobs\PosAppReleasesJob;
use App\Domains\PosModules\Jobs\RemovePosModuleExtraZipFilesJob;
use App\Domains\Product\Jobs\PosProductsZipJob;
use App\Domains\Product\Jobs\ProductAgeingTableUpdatesMainJob;
use App\Domains\ProductChannelReference\Jobs\CheckProductChannelReferenceMainJob;
use App\Domains\PromoterCommission\Jobs\PromoterCommissionGenerationChunkingJob;
use App\Domains\Region\Jobs\DailyTotalSalesMailToRegionsJob;
use App\Domains\SaleTarget\Jobs\SaleAchievedTargetJob;
use App\Domains\SellThroughAggregate\Jobs\UpdateDailyAggregateMainDataJob;
use App\Domains\StockTransferAverageLeadDays\Jobs\AggregatedAverageTransferDaysJob;
use App\Domains\StoreDayClose\Jobs\AutomaticDayCloseJob;
use App\Domains\StoreManagerAuthorizationCode\Jobs\StoreManagerAuthorizationCodeJob;
use App\Domains\StoreWiseDailyTotal\Jobs\StoreWiseDailySalesForClosedCounterJob;
use App\Domains\StoreWiseDailyTotal\Jobs\StoreWiseDailySalesJob;
use App\Domains\TopTwentyAggregateData\Jobs\DailyTopTwentyAggregateDataJob;
use App\Domains\Voucher\Jobs\VoucherExpirationJob;
use App\Domains\VoucherConfiguration\Jobs\GenerateMemberBirthdayVouchersJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Spatie\Health\Commands\DispatchQueueCheckJobsCommand;
use Spatie\Health\Commands\RunHealthChecksCommand;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command(DispatchQueueCheckJobsCommand::class)->everyMinute();

        $schedule->command(RunHealthChecksCommand::class)->everyFiveMinutes();

        $schedule->command('auth:clear-resets')->daily();

        $schedule->job(new UpdateCompanyCounterDetailsJob())->daily();

        $schedule->command('clean:directories')->weeklyOn(1, '23:59:00');

        $schedule->command('cache:prune-stale-tags')->hourly();

        $schedule->command(DispatchJobs::class, [
            '--class' => GenerateMemberBirthdayVouchersJob::class,
            '--queue' => config('horizon.default_queue_name'),
        ])->dailyAt('01:00');

        $schedule->command(DispatchJobs::class, [
            '--class' => AutomatedNotificationJob::class,
            '--queue' => 'medium',
        ])->daily();

        $schedule->command(DispatchJobs::class, [
            '--class' => PromoterCommissionGenerationChunkingJob::class,
            '--queue' => 'high',
        ])->monthlyOn(1, '05:00');

        $schedule->command(DispatchJobs::class, [
            '--class' => CreditNoteExpirationJob::class,
            '--queue' => config('horizon.default_queue_name'),
        ])->dailyAt('00:10');

        $schedule->command(DispatchJobs::class, [
            '--class' => LoyaltyPointExpirationJob::class,
            '--queue' => config('horizon.default_queue_name'),
        ])->dailyAt('00:20');

        $schedule->command(DispatchJobs::class, [
            '--class' => AutomaticDayCloseJob::class,
            '--queue' => config('horizon.default_queue_name'),
        ])->everyFiveMinutes();

        $schedule->command(DispatchJobs::class, [
            '--class' => GiftCardExpirationJob::class,
            '--queue' => config('horizon.default_queue_name'),
        ])->dailyAt('00:30');

        $schedule->command(DispatchJobs::class, [
            '--class' => DailySalesUpdateJob::class,
            '--queue' => config('horizon.default_queue_name'),
        ])->hourly();

        $schedule->command(DispatchJobs::class, [
            '--class' => DailyTopTenStoreSalesJob::class,
            '--queue' => config('horizon.default_queue_name'),
        ])->everyFifteenMinutes();

        $schedule->command(DispatchJobs::class, [
            '--class' => StoreWiseDailySalesJob::class,
            '--queue' => config('horizon.default_queue_name'),
        ])->hourly();

        $schedule->command(DispatchJobs::class, [
            '--class' => StoreWiseDailySalesForClosedCounterJob::class,
            '--queue' => config('horizon.default_queue_name'),
        ])->dailyAt('02:00');

        $schedule->command(DispatchJobs::class, [
            '--class' => PosAppReleasesJob::class,
            '--queue' => config('horizon.default_queue_name'),
        ])->everyThreeHours();

        $schedule->command(DispatchJobs::class, [
            '--class' => DeleteOldNotificationsJob::class,
            '--queue' => config('horizon.default_queue_name'),
        ])->dailyAt('00:40');

        $schedule->command(DispatchJobs::class, [
            '--class' => SaleAchievedTargetJob::class,
            '--queue' => 'medium',
        ])->dailyAt('00:50');

        $schedule->command(DispatchJobs::class, [
            '--class' => StoreManagerAuthorizationCodeJob::class,
            '--queue' => config('horizon.default_queue_name'),
        ])->hourly();

        $schedule->command(DispatchJobs::class, [
            '--class' => ProductAgeingTableUpdatesMainJob::class,
            '--queue' => config('horizon.default_queue_name'),
        ])->dailyAt('02:30');

        $schedule->command(DispatchJobs::class, [
            '--class' => DreamPriceOverlayRestrictionJob::class,
            '--queue' => config('horizon.default_queue_name'),
        ])->hourly();

        $schedule->command('generate:ioi-city-mall-sales-files')->dailyAt('00:30');

        $schedule->command('upload:ioi-city-mall-sales-files')->dailyAt('01:00');

        $schedule->command('trx:send-sales')->dailyAt('00:30');

        $schedule->command(DispatchJobs::class, [
            '--class' => AggregatedAverageTransferDaysJob::class,
            '--queue' => config('horizon.default_queue_name'),
        ])->dailyAt('01:10');

        $schedule->command(DispatchJobs::class, [
            '--class' => DailyTotalSalesMailToRegionsJob::class,
            '--queue' => config('horizon.default_queue_name'),
        ])->dailyAt('01:25');

        $schedule->command(DispatchJobs::class, [
            '--class' => DailyTopTwentyAggregateDataJob::class,
            '--queue' => config('horizon.default_queue_name'),
        ])->dailyAt('01:15');

        $schedule->command(DispatchJobs::class, [
            '--class' => CheckProductChannelReferenceMainJob::class,
            '--queue' => config('horizon.default_queue_name'),
        ])->dailyAt('02:15');

        $schedule->command(DispatchJobs::class, [
            '--class' => PosProductsZipJob::class,
            '--queue' => config('horizon.default_queue_name'),
        ])->everySixHours();

        $schedule->command(DispatchJobs::class, [
            '--class' => RemovePosModuleExtraZipFilesJob::class,
            '--queue' => config('horizon.default_queue_name'),
        ])->dailyAt('01:20');

        $schedule->command(DispatchJobs::class, [
            '--class' => CurrencyRateUpdateJob::class,
            '--queue' => config('horizon.default_queue_name'),
        ])->dailyAt('00:00');

        $schedule->command(DispatchJobs::class, [
            '--class' => UpdateDailyAggregateMainDataJob::class,
            '--queue' => config('horizon.default_queue_name'),
        ])->dailyAt('03:00');

        $schedule->command(DispatchJobs::class, [
            '--class' => VoucherExpirationJob::class,
        ])->dailyAt('01:30');

        $schedule->command(DispatchJobs::class, [
            '--class' => SyncAzentioItemsJob::class,
        ])->dailyAt('12:00');

        $schedule->command(DispatchJobs::class, [
            '--class' => SyncAzentioMembersJob::class,
        ])->dailyAt('12:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
