<?php

declare(strict_types=1);

namespace App\Domains\StoreDayClose\Jobs;

use App\Domains\CounterUpdate\CounterUpdateQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\StoreDayClose\Mail\SendFailedAutomaticDayCloseMail;
use App\Domains\StoreDayClose\Services\StoreDayCloseService;
use App\Domains\StoreDayClose\StoreDayCloseQueries;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Models\Employee;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class AutomaticDayCloseJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $locationQueries = resolve(LocationQueries::class);
        $storeDayCloseQueries = resolve(StoreDayCloseQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);

        $locations = $locationQueries->getWithAutomaticDayCloseTimeAndName();

        foreach ($locations as $location) {
            /** @var Carbon $storeTimeFormat */
            $storeTimeFormat = Carbon::createFromFormat('H:i:s', $location->automatic_day_close_time);
            $storeTime = $storeTimeFormat->format('H:i');
            $currentTime = Carbon::now()->format('H:i');

            if ($storeTime !== $currentTime) {
                continue;
            }

            $lastStoreDayClose = $storeDayCloseQueries->getLastDayClose($location->id);
            $totalOpenCounters = $counterUpdateQueries->getOpenCountersCountFilterByStoreAndDates(
                $location->id,
                $lastStoreDayClose
            );

            if ($totalOpenCounters > 0) {
                $this->sendMailAndLogDetails($location);

                continue;
            }

            $storeDayCloseService = resolve(StoreDayCloseService::class);

            DB::beginTransaction();

            try {
                $storeDayCloseService->addStoreDayClose(
                    $counterUpdateQueries,
                    $storeDayCloseQueries,
                    $location,
                    $lastStoreDayClose,
                );

                DB::commit();
            } catch (Throwable $throwable) {
                Log::error('Automatic-Day-Close:', [
                    'error_message' => 'Error message: ' . $throwable->getMessage(),
                    'error_code' => 'Error code: ' . $throwable->getCode(),
                    'file' => 'File: ' . $throwable->getFile(),
                    'line' => 'Line: ' . $throwable->getLine(),
                    'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                    'Full error' => [$throwable],
                ]);

                DB::rollBack();

                $this->fail($throwable);
            }
        }
    }

    private function sendMailAndLogDetails(Location $location): void
    {
        Log::channel('automatic_day_close')->info('automatic_day_close', [
            'Automatic-Day-Close' => 'Not all registers are closed for the ' . $location->name . '.',
        ]);

        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $storeManagers = $storeManagerQueries->getByStoreIdWithEmployee($location->id);

        foreach ($storeManagers as $storeManager) {
            /** @var Employee $employee */
            $employee = $storeManager->employee;

            if (! $employee->email) {
                continue;
            }

            try {
                Mail::to($employee->email)
                    ->send(new SendFailedAutomaticDayCloseMail($employee, $location));
            } catch (Throwable $throwable) {
                Log::error('Send Failed Automatic Day Close Mail', [
                    'error_message' => 'Error message: ' . $throwable->getMessage(),
                    'error_code' => 'Error code: ' . $throwable->getCode(),
                    'email' => $employee->email,
                    'Employee' => $employee->id,
                    'file' => 'File: ' . $throwable->getFile(),
                    'line' => 'Line: ' . $throwable->getLine(),
                    'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                    'Full error' => [$throwable],
                ]);
            }
        }
    }
}
