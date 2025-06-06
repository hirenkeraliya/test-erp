<?php

declare(strict_types=1);

namespace App\Domains\Product\Jobs;

use App\Models\Company;
use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProductCreateMasterProductMainJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(): void
    {
        $companies = Company::select('id', 'name')->get();
        foreach ($companies as $company) {
            $product = Product::select('id')
                ->where('company_id', $company->id)
                ->whereNull('master_product_id')
                ->orderBy('id', 'desc')
                ->first();

            $lastId = 0;
            if ($product) {
                $lastId = $product->id;
            }

            if ($lastId < 500) {
                $lastId = 500;
            }

            $endLastId = 0;
            for ($endId = 500; $endId <= $lastId; $endId += 500) {
                $endLastId = $endId;
                $startId = $endId - 499;
                ProductCreateMasterProductJob::dispatch($startId, $endId)->onQueue('high');
            }

            if ($endLastId < $lastId) {
                ProductCreateMasterProductJob::dispatch($endLastId + 1, $lastId)->onQueue('high');
            }
        }
    }
}
