<?php

declare(strict_types=1);

namespace App\Domains\Product\Jobs;

use App\Domains\PosModules\Services\PosModuleZipService;
use App\Domains\Product\ProductQueries;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class PosProductsZipJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(): void
    {
        if (! config('services.pos_modules.allow_pos_modules_zip')) {
            return;
        }

        Log::channel('pos_modules')->info('pos_modules', [
            "Product's zip job start time: " . Carbon::now()->format('Y-m-d H:i:s'),
        ]);

        try {
            $this->generateJsonFilesOfTheModules();

            $posModuleZipService = resolve(PosModuleZipService::class);
            $posModuleZipService->createModuleZip('products');

            Log::channel('pos_modules')->info('pos_modules', ['Finished zip products.']);
        } catch (Throwable $throwable) {
            Log::channel('pos_modules')->error('pos_modules', [
                "There is an error with the product's zip job : " . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . print_r($throwable->getTrace(), true),
                'Full error' => [$throwable],
            ]);
        }

        Log::channel('pos_modules')->info('pos_modules', [
            'The end time of the job for the Products zip is.: ' . Carbon::now()->format('Y-m-d H:i:s'),
        ]);
    }

    private function generateJsonFilesOfTheModules(): void
    {
        $productQueries = resolve(ProductQueries::class);

        $productQueries->getPosProductListForZip();
    }
}
