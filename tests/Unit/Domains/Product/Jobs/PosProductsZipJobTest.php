<?php

declare(strict_types=1);

use App\Domains\PosModules\Services\PosModuleZipService;
use App\Domains\Product\Jobs\PosProductsZipJob;
use App\Domains\Product\ProductQueries;

test('It can create zip file', function (): void {
    if (! config('services.pos_modules.allow_pos_modules_zip')) {
        $this->assertTrue(true);

        return;
    }

    $this->mock(ProductQueries::class, function ($mock): void {
        $mock->shouldReceive('getPosProductListForZip')
            ->times(1);
    });

    $this->mock(PosModuleZipService::class, function ($mock): void {
        $mock->shouldReceive('createModuleZip')
            ->times(1);
    });

    PosProductsZipJob::dispatch()->onQueue(config('horizon.default_queue_name'));
});
