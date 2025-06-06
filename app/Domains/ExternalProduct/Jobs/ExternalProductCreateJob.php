<?php

declare(strict_types=1);

namespace App\Domains\ExternalProduct\Jobs;

use App\Domains\Company\CompanyQueries;
use App\Domains\ExternalCompany\ExternalCompanyQueries;
use App\Domains\ExternalConnection\Services\ExternalConnectionService;
use App\Domains\Product\ProductQueries;
use App\Models\ExternalConnection;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ExternalProductCreateJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(
        private readonly int $externalCompanyId,
        private readonly int $productId,
    ) {
    }

    public function handle(): void
    {
        $externalCompanyQueries = resolve(ExternalCompanyQueries::class);
        $externalCompany = $externalCompanyQueries->getExternalCompanyWithRelationById($this->externalCompanyId);

        /** @var ExternalConnection $externalConnection */
        $externalConnection = $externalCompany->externalConnection;
        $externalConnectionService = resolve(ExternalConnectionService::class);
        $productQueries = resolve(ProductQueries::class);
        $product = $productQueries->getActiveProductByIdWithAllRelations($this->productId);

        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($product->company_id);

        Log::channel('external_product_create_job')->info('external_product_create_job', [
            'External Product create job start time is: ' . Carbon::now()->format(
                'Y-m-d H:i:s'
            ) . 'and product upc is : ' . $product->upc . ' and company is: ' . $externalCompany->external_company_id,
        ]);

        try {
            $product->company_id = $externalCompany->external_company_id;
            $product['sender_company'] = [
                'id' => $company->id,
                'name' => $company->name,
            ];
            $externalConnectionService->sendProductDataExternalConnection(
                $externalConnection,
                $product,
                $externalCompany->external_company_id,
                $company->id
            );

            Log::channel('external_product_create_job')->info('external_product_create_job', [
                'External Product create job completion time is: ' . Carbon::now()->format(
                    'Y-m-d H:i:s'
                ) . 'and product upc is : ' . $product->upc . ' and company is: ' . $externalCompany->external_company_id,
            ]);
        } catch (Throwable $throwable) {
            DB::rollBack();

            Log::error([
                'error_name' => 'External Product Create Job  error:',
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
            ]);

            $this->fail($throwable);
        }
    }
}
