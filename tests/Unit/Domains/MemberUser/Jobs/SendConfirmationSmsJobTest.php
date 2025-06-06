<?php

declare(strict_types=1);

use App\Domains\Member\Jobs\SendConfirmationSmsJob;
use App\Domains\SmsHistory\SmsHistoryQueries;
use App\Services\CelcomSmsService;

test(
    'SendConfirmationSmsJob job calls respective methods and send the otp as expected',
    function (): void {
        $this->mock(CelcomSmsService::class, function ($mock): void {
            $mock->shouldReceive('sendSms')
                ->once()
                ->andReturn([
                    'status' => true,
                ]);

            $mock->shouldReceive('isEnabled')
               ->once()
                ->andReturn(true);
        });

        $this->mock(SmsHistoryQueries::class, function ($mock): void {
            $mock->shouldReceive('updateById')
                ->once();
        });

        SendConfirmationSmsJob::dispatch('1111111111', 'testing message', 1)->onQueue(
            config('horizon.default_queue_name')
        );
    }
);
