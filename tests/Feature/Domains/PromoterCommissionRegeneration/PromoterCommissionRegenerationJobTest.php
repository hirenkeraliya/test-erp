<?php

declare(strict_types=1);

use App\Domains\PromoterCommission\Jobs\PromoterCommissionGenerationChunkingJob;
use App\Domains\PromoterCommission\PromoterCommissionQueries;
use App\Domains\PromoterCommissionRegeneration\Jobs\PromoterCommissionRegenerationJob;
use App\Domains\PromoterCommissionRegeneration\PromoterCommissionRegenerationQueries;
use App\Domains\PromoterCommissionUpdate\PromoterCommissionUpdateQueries;
use App\Models\PromoterCommission;
use App\Models\PromoterCommissionRegeneration;
use Illuminate\Support\Facades\Bus;

test(
    'PromoterCommissionRegenerationJob job calls respective methods and store promoter commission as expected.',
    function (): void {
        Bus::fake()->except([PromoterCommissionRegenerationJob::class]);

        $promoterCommissionRegeneration = PromoterCommissionRegeneration::factory()->create();

        $promoterCommission = PromoterCommission::factory()->make([
            'id' => 1,
            'promoter_id' => 1,
        ]);

        $this->mock(PromoterCommissionQueries::class, function ($mock) use ($promoterCommission): void {
            $mock->shouldReceive('getIdsByPeriod')
                ->once()
                ->andReturn(collect([$promoterCommission]));

            $mock->shouldReceive('deleteByPeriod')
                ->once();
        });

        $this->mock(PromoterCommissionUpdateQueries::class, function ($mock): void {
            $mock->shouldReceive('deleteByPromoterCommissionIds')
                ->once();
        });

        $this->mock(PromoterCommissionRegenerationQueries::class, function ($mock): void {
            $mock->shouldReceive('markAsStarted')
                ->once();
        });

        PromoterCommissionRegenerationJob::dispatch($promoterCommissionRegeneration->id)->onQueue(
            config('horizon.default_queue_name')
        );

        Bus::assertDispatched(PromoterCommissionGenerationChunkingJob::class);
    }
);
