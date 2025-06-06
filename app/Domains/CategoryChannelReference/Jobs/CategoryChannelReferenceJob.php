<?php

declare(strict_types=1);

namespace App\Domains\CategoryChannelReference\Jobs;

use App\Domains\Category\CategoryQueries;
use App\Domains\CategoryChannelReference\CategoryChannelReferenceQueries;
use App\Domains\SaleChannel\Enums\SaleChannelTypes;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Services\WebspertIntegrationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class CategoryChannelReferenceJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $saleChannelQueries = resolve(SaleChannelQueries::class);
        $categoryChannelReferenceQueries = resolve(CategoryChannelReferenceQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);
        $webspertIntegrationService = resolve(WebspertIntegrationService::class);

        $saleChannels = $saleChannelQueries->getSpecificTypeOfSaleChannelWithWebHooks(
            SaleChannelTypes::WEBSPERT_ECOMMERCE
        );

        if ($saleChannels->isEmpty()) {
            Log::channel('category_channel_reference')->info('sale channel is not available');

            return;
        }

        DB::beginTransaction();
        try {
            $staticPath = '/product/category/get_category_list';
            foreach ($saleChannels as $saleChannel) {
                $categories = $webspertIntegrationService->getEcommerceCategories($saleChannel, $staticPath);

                if (! empty($categories)) {
                    foreach ($categories['data']['categories'] as $category) {
                        $firstCategory = $categoryQueries->getIdByNameWithoutCompanyId($category['category_name']);

                        if (null === $firstCategory) {
                            return;
                        }

                        $categoryChannelReferenceQueries->addNew([
                            'sale_channel_id' => $saleChannel->getKey(),
                            'category_id' => $firstCategory,
                            'external_category_id' => $category['category_id'],
                        ]);

                        $categoryQueries->updateIsAvailableInEcommerce($firstCategory);
                    }
                }
            }

            DB::commit();
        } catch (Throwable $throwable) {
            DB::rollBack();

            Log::error([
                'error_name' => 'Category Channel Reference Job job error:',
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
