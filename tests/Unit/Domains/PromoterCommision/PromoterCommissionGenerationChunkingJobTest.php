<?php

declare(strict_types=1);

use App\Domains\Promoter\PromoterQueries;
use App\Domains\PromoterCommission\Jobs\PromoterCommissionGenerationChunkingJob;
use App\Domains\PromoterCommission\Jobs\PromoterCommissionGenerationJob;
use App\Domains\PromoterCommission\PromoterCommissionQueries;
use App\Models\Promoter;
use Illuminate\Support\Facades\Bus;

test(
    'PromoterCommissionGenerationChunkingJob job calls respective methods and store promoter commission as expected.',
    function (): void {
        Bus::fake()->except([PromoterCommissionGenerationChunkingJob::class]);

        $promoter = Promoter::factory()->make([
            'id' => 1,
            'employee_id' => 1,
        ]);

        $this->mock(PromoterCommissionQueries::class, function ($mock): void {
            $mock->shouldReceive('entryExistsForPeriod')
                ->once()
                ->andReturn(false);
        });

        $this->mock(PromoterQueries::class, function ($mock) use ($promoter): void {
            $mock->shouldReceive('getIds')
                ->once()
                ->andReturn(collect([$promoter]));
        });

        PromoterCommissionGenerationChunkingJob::dispatch()->onQueue(config('horizon.default_queue_name'));

        Bus::assertDispatched(PromoterCommissionGenerationJob::class);
    }
);

test(
    'PromoterCommissionGenerationChunkingJob job throw exception when commission already generated.',
    function (): void {
        $this->mock(PromoterCommissionQueries::class, function ($mock): void {
            $mock->shouldReceive('entryExistsForPeriod')
                ->once()
                ->andReturn(true);
        });

        PromoterCommissionGenerationChunkingJob::dispatch()->onQueue(config('horizon.default_queue_name'));
    }
)->throws(Exception::class);
