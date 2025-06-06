<?php

declare(strict_types=1);

namespace App\Domains\ExternalProduct\Jobs;

use App\Domains\Brand\BrandQueries;
use App\Domains\Category\CategoryQueries;
use App\Domains\Color\ColorQueries;
use App\Domains\Department\DepartmentQueries;
use App\Domains\ExternalProduct\Enums\ExternalProductStatuses;
use App\Domains\ExternalProduct\ExternalProductQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\Season\SeasonQueries;
use App\Domains\Size\SizeQueries;
use App\Domains\Style\StyleQueries;
use App\Domains\Tag\TagQueries;
use App\Domains\Template\TemplateQueries;
use App\Domains\UnitOfMeasure\UnitOfMeasureQueries;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class CreateProductFromExternalProductJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(
        private readonly int $externalProductId,
        private readonly int $companyId,
        private readonly User $user,
    ) {
    }

    public function handle(): void
    {
        $productVariant = config('app.product_variant');
        $externalProductQueries = resolve(ExternalProductQueries::class);
        $externalProduct = $externalProductQueries->getExternalProductByIdAndCompanyId(
            $this->externalProductId,
            $this->companyId
        );

        Log::channel('create_product_from_external_product_job')->info('create_product_from_external_product_job', [
            'Create product from external product job start time is: ' . Carbon::now()->format(
                'Y-m-d H:i:s'
            ) . 'and external product id is : ' . $externalProduct->id . ' and company is: ' . $externalProduct->external_company_id,
        ]);

        $companyId = $externalProduct->company_id;

        DB::beginTransaction();
        try {
            $productData = $externalProduct->product_details;

            $productQueries = resolve(ProductQueries::class);
            $brandQueries = resolve(BrandQueries::class);
            $colorQueries = resolve(ColorQueries::class);
            $sizeQueries = resolve(SizeQueries::class);
            $styleQueries = resolve(StyleQueries::class);
            $departmentQueries = resolve(DepartmentQueries::class);
            $categoryQueries = resolve(CategoryQueries::class);
            $tagQueries = resolve(TagQueries::class);
            $unitOfMeasureQueries = resolve(UnitOfMeasureQueries::class);
            $seasonQueries = resolve(SeasonQueries::class);
            $templateQueries = resolve(TemplateQueries::class);
            $categoryIds = [];
            $tagIds = [];

            if (! $productVariant) {
                if (isset($productData['brand']) && null !== $productData['brand']) {
                    $brandId = $brandQueries->firstOrCreateByName($productData['brand']['name'], $companyId);
                }

                if (isset($productData['color']) && null !== $productData['color']) {
                    $colorId = $colorQueries->getIdByName($productData['color']['name'], $companyId);
                }

                if (isset($productData['size']) && null !== $productData['size']) {
                    $sizeId = $sizeQueries->getIdByName($productData['size']['name'], $companyId);
                }

                if (isset($productData['style']) && null !== $productData['style']) {
                    $styleId = $styleQueries->getIdByName($productData['style']['name'], $companyId);
                }

                if (isset($productData['department']) && null !== $productData['department']) {
                    $departmentId = $departmentQueries->getIdByName($productData['department']['name'], $companyId);
                }

                if (isset($productData['categories']) && null !== $productData['categories']) {
                    foreach ($productData['categories'] as $category) {
                        $categoryIds[] = $categoryQueries->getIdByNameAndCompanyId($category['name'], $companyId);
                    }
                }

                if (isset($productData['tags']) && null !== $productData['tags']) {
                    foreach ($productData['tags'] as $tag) {
                        $tagIds[] = $tagQueries->getIdByNameAndCompanyId($tag['name'], $companyId);
                    }
                }

                if (isset($productData['unit_of_measure']) && null !== $productData['unit_of_measure']) {
                    $unitOfMeasureId = $unitOfMeasureQueries->getIdByNameAndCompanyId(
                        $productData['unit_of_measure']['name'],
                        $companyId
                    );
                }

                if (isset($productData['season']) && null !== $productData['season']) {
                    $sessionId = $seasonQueries->getIdByName($productData['season']['name'], $companyId);
                }
            } elseif (isset($productData['master_product']) && array_key_exists('master_product', $productData)) {
                if (isset($productData['master_product']['brand']) && null !== $productData['master_product']['brand']) {
                    $brandId = $brandQueries->firstOrCreateByName(
                        $productData['master_product']['brand']['name'],
                        $companyId
                    );
                }

                if (isset($productData['master_product']['department']) && null !== $productData['master_product']['department']) {
                    $departmentId = $departmentQueries->getIdByName(
                        $productData['master_product']['department']['name'],
                        $companyId
                    );
                }

                if (isset($productData['master_product']['categories']) && null !== $productData['master_product']['categories']) {
                    foreach ($productData['master_product']['categories'] as $category) {
                        $categoryIds[] = $categoryQueries->getIdByNameAndCompanyId($category['name'], $companyId);
                    }
                }

                if (isset($productData['master_product']['tags']) && null !== $productData['master_product']['tags']) {
                    foreach ($productData['master_product']['tags'] as $tag) {
                        $tagIds[] = $tagQueries->getIdByNameAndCompanyId($tag['name'], $companyId);
                    }
                }

                if (isset($productData['master_product']['unit_of_measure']) && null !== $productData['master_product']['unit_of_measure']) {
                    $unitOfMeasureId = $unitOfMeasureQueries->getIdByNameAndCompanyId(
                        $productData['master_product']['unit_of_measure']['name'],
                        $companyId
                    );
                }

                if (isset($productData['master_product']['variant_template']) && null !== $productData['master_product']['variant_template']) {
                    $templateId = $templateQueries->getIdByNameAndCompanyId(
                        $productData['master_product']['variant_template'],
                        $companyId
                    );
                }
            }

            $productData['company_id'] = $companyId;
            $productData['unit_of_measure_id'] = $unitOfMeasureId ?? null;
            $productData['season_id'] = $sessionId ?? null;
            $productData['department_id'] = $departmentId ?? null;
            $productData['color_id'] = $colorId ?? null;
            $productData['size_id'] = $sizeId ?? null;
            $productData['brand_id'] = $brandId ?? null;
            $productData['style_id'] = $styleId ?? null;
            $productData['tag_ids'] = $tagIds;
            $productData['category_ids'] = $categoryIds;
            if ($productVariant) {
                $productData['template_id'] = $templateId ?? null;
                $productQueries->addNewFromExternalProductForVariant($productData, $this->user);
            } else {
                $productQueries->addNewFromExternalProduct($productData, $this->user);
            }

            $externalProductQueries->changeStatus($externalProduct, ExternalProductStatuses::CREATED->value);

            DB::commit();
        } catch (Throwable $throwable) {
            DB::rollBack();

            Log::error('Create Product From External Product Job error', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            $this->fail($throwable);
        }

        Log::channel('create_product_from_external_product_job')->info('create_product_from_external_product_job', [
            'Create product from external product job end time is: ' . Carbon::now()->format(
                'Y-m-d H:i:s'
            ) . 'and external product id is : ' . $externalProduct->id . ' and company is: ' . $externalProduct->external_company_id,
        ]);
    }
}
