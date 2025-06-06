<?php

declare(strict_types=1);

namespace App\Domains\MasterProduct\Services;

use App\Domains\Attribute\Enums\FieldType;
use App\Domains\BrandChannelReference\BrandChannelReferenceQueries;
use App\Domains\Category\CategoryQueries;
use App\Domains\CategoryChannelReference\CategoryChannelReferenceQueries;
use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\Company\CompanyQueries;
use App\Domains\Company\Enums\CommissionTypes;
use App\Domains\Department\DepartmentQueries;
use App\Domains\MasterProduct\DataObjects\MasterProductData;
use App\Domains\MasterProduct\MasterProductQueries;
use App\Domains\MasterProductChannelReference\MasterProductChannelReferenceQueries;
use App\Domains\Membership\MembershipQueries;
use App\Domains\PackageType\PackageTypeQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\Product\DataObjects\ProductData;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Product\Enums\Statuses;
use App\Domains\SaleChannel\Enums\SaleChannelTypes;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Domains\Tag\TagQueries;
use App\Domains\Template\TemplateQueries;
use App\Domains\UnitOfMeasure\UnitOfMeasureQueries;
use App\Domains\Vendor\VendorQueries;
use App\Exceptions\RedirectBackWithErrorException;
use App\Models\BrandChannelReference;
use App\Models\MasterProduct;
use App\Models\MasterProductChannelReference;
use App\Models\Product;
use App\Models\SaleChannel;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Spatie\LaravelData\DataCollection;

class MasterProductService
{
    public function checkRequestDetails(int $brandId, int $companyId, MasterProductData $masterProductData): void
    {
        $companyQueries = resolve(CompanyQueries::class);

        $this->validateTypeIdAndMinimumPrice($masterProductData);
        $this->validateItemVariantLoyaltyPoint($masterProductData);

        $hasAllBrandsAttachedInCompany = $companyQueries->hasAllBrandsAttached($companyId, [$brandId]);

        if (! $hasAllBrandsAttachedInCompany) {
            throw new RedirectBackWithErrorException('The selected brand does not match with the current company.');
        }

        /** @var Collection $variants */
        $variants = $masterProductData->variants;

        foreach ($variants as $variant) {
            if (! empty($variant->sale_channel_ids)) {
                $saleChannelQueries = resolve(SaleChannelQueries::class);

                $allSaleChannelExist = $saleChannelQueries->doAllSaleChannelExist(
                    $companyId,
                    $variant->sale_channel_ids
                );

                if (! $allSaleChannelExist) {
                    throw new RedirectBackWithErrorException(
                        'One of the selected sale channels does not match the current company.'
                    );
                }
            }
        }
    }

    public function validateBoxProductVariantLoyaltyPointMembership(MasterProductData $masterProductData): void
    {
        if (! $masterProductData->variants instanceof DataCollection) {
            return;
        }

        foreach ($masterProductData->variants as $variant) {
            foreach ($variant->boxes as $box) {
                if (! array_key_exists('box_product_loyalty_points', $box)) {
                    continue;
                }

                /** @var array $boxProductVariantLoyaltyPointsArray */
                $boxProductVariantLoyaltyPointsArray = $box['box_product_loyalty_points'];

                $boxProductLoyaltyPoints = collect($boxProductVariantLoyaltyPointsArray);
                if (
                    $boxProductLoyaltyPoints->count()
                    !== $boxProductLoyaltyPoints->pluck('membership_id')->unique()->count()
                ) {
                    throw new RedirectBackWithErrorException(
                        $variant->name.' Box Product Variant Membership field is duplicate values.'
                    );
                }
            }
        }
    }

    public function validateItemVariantLoyaltyPoint(MasterProductData $masterProductData): void
    {
        if (! $masterProductData->variants instanceof DataCollection) {
            return;
        }

        foreach ($masterProductData->variants as $variant) {
            if (! array_key_exists('tiers', $variant->toArray())) {
                continue;
            }

            /** @var array $tires */
            $tires = $variant->tiers;

            $productVariantLoyaltyPoints = collect($tires);
            if (
                $productVariantLoyaltyPoints->count()
                !== $productVariantLoyaltyPoints->pluck('membership_id')->unique()->count()
            ) {
                throw new RedirectBackWithErrorException(
                    $variant->name.' Product Variant Membership field is duplicate values.'
                );
            }
        }
    }

    private function validateTypeIdAndMinimumPrice(MasterProductData $masterProductData): void
    {
        if ((int) $masterProductData->type_id === ProductTypes::REGULAR_PRODUCT->value || (int) $masterProductData->type_id === ProductTypes::ASSEMBLY_PRODUCT->value) {
            return;
        }

        if (! $masterProductData->variants instanceof DataCollection) {
            return;
        }

        foreach ($masterProductData->variants as $variant) {
            if ($variant->minimum_price && 0.0 !== $variant->minimum_price) {
                return;
            }

            throw new RedirectBackWithErrorException(
                'To proceed, a minimum price needs to be established for non-standard products.'
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function getCommonRecords(int $companyId): array
    {
        $unitOfMeasureQueries = resolve(UnitOfMeasureQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);
        $company = $companyQueries->getByIdWithPromoterCommissionDetails($companyId);
        $packageTypeQueries = resolve(PackageTypeQueries::class);
        $templateQueries = resolve(TemplateQueries::class);
        $vendorQueries = resolve(VendorQueries::class);
        $saleChannelQueries = resolve(SaleChannelQueries::class);
        $saleChannels = $saleChannelQueries->getAllByCompanyId($companyId);
        $membershipQueries = resolve(MembershipQueries::class);
        $tagQueries = resolve(TagQueries::class);
        $departmentQueries = resolve(DepartmentQueries::class);

        return [
            'unitOfMeasures' => $unitOfMeasureQueries->getWithBasicColumns($companyId),
            'brands' => $companyQueries->getByIdWithBrands($companyId)->brands,
            'categories' => $categoryQueries->getMainCategoriesWithBasicColumns($companyId),
            'departments' => $departmentQueries->getWithBasicColumns($companyId),
            'vendors' => $vendorQueries->getWithBasicColumns($companyId),
            'types' => ProductTypes::formattedForSelection(),
            'discountTypes' => DiscountTypes::getFormattedArrayForStaticUse(),
            'commissionTypes' => CommissionTypes::toArray(),
            'company' => $company,
            'assemblyProductTypeStatic' => [
                'assemblyProduct' => ProductTypes::ASSEMBLY_PRODUCT->value,
            ],
            'purchaseCost' => 'product_' . PermissionList::PRODUCT_PURCHASE_COST->value,
            'defaultTypeStatic' => [
                'regularProduct' => ProductTypes::REGULAR_PRODUCT->value,
                'assemblyProduct' => ProductTypes::ASSEMBLY_PRODUCT->value,
            ],
            'packageTypes' => $packageTypeQueries->getWithBasicColumns($companyId),
            'templates' => $templateQueries->fetchForDropdown($companyId),
            'variantTemplates' => $templateQueries->fetchForVariantDropdown($companyId),
            'memberships' => $membershipQueries->getWithBasicColumns($companyId),
            'fieldTypes' => FieldType::getFormattedArrayForStaticUse(),
            'saleChannels' => $saleChannels,
            'tags' => $tagQueries->getWithBasicColumns($companyId),
        ];
    }

    public function prepareCustomFieldValues(Collection $attachedTemplates): Collection
    {
        return $attachedTemplates->map(function ($attachedTemplate): array {
            $template = $attachedTemplate->template;
            $attributes = $this->transformAttributes($template->attributes);

            return [
                'id' => $template->id,
                'name' => $template->name,
                'attributes' => $attributes,
            ];
        });
    }

    public function addUpdateDetails(MasterProduct $masterProduct, SaleChannel $saleChannel): void
    {
        Log::channel('master_product')->info('Start creating or updating the master product in eCommerce.', [
            'Start time for master product creation or updating' => Carbon::now()->format('Y-m-d H:i:s'),
            'master product id: ' . $masterProduct->getKey(),
        ]);

        $masterProductChannelReferenceQueries = resolve(MasterProductChannelReferenceQueries::class);

        foreach ($saleChannel->saleChannelWebhookUrls as $saleChannelWebhookUrl) {
            $url = $saleChannelWebhookUrl->url;

            if (SaleChannelTypes::ECOMMERCE === $saleChannel->type_id) {
                $masterProductChannelReference = $masterProductChannelReferenceQueries->getByMasterProductIdAndSaleChannelId(
                    $masterProduct->id,
                    $saleChannel->id
                );

                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $saleChannel->secret,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])->timeout(config('services.http_time_out'))->post($url, [
                    'masterProduct' => $this->preparedRecords(
                        $masterProduct,
                        $masterProductChannelReference,
                        $saleChannel
                    ),
                ]);

                if ($response->successful()) {
                    $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

                    Log::channel('master_product')->info('Response: Master product in E-Commerce', [
                        'response' => $responseData,
                    ]);

                    if (array_key_exists('product_id', $responseData) && ! $masterProductChannelReference) {
                        $masterProductChannelReferenceQueries->addNew([
                            'sale_channel_id' => $saleChannel->id,
                            'master_product_id' => $masterProduct->id,
                            'external_master_product_id' => $responseData['product_id'],
                        ]);
                    }
                } else {
                    Log::channel('master_product')->info('Response: Error on master product in E-Commerce', [
                        'status_code' => $response->status(),
                        'response_body' => $response->body() ?: 'No response body provided',
                        'request_data' => [
                            'master_product_id' => $masterProduct->getKey(),
                            'saleChannel_id' => $saleChannel->getKey(),
                        ],
                    ]);
                }
            }
        }

        Log::channel('master_product')->info('End creating or updating the master product in eCommerce.', [
            'End time for master product creation or updation' => Carbon::now()->format('Y-m-d H:i:s'),
            'master product id: ' . $masterProduct->getKey(),
        ]);
    }

    public function createOrUpdateFromProduct(Product $product, ProductData $productData): void
    {
        $masterProductQueries = resolve(MasterProductQueries::class);
        $masterProductQueries->createOrUpdateFromProduct($product, $productData);
    }

    private function preparedRecords(
        MasterProduct $masterProduct,
        ?MasterProductChannelReference $masterProductChannelReference,
        SaleChannel $saleChannel
    ): array {
        return [
            'existing_id' => $masterProductChannelReference?->external_master_product_id,
            'name' => $masterProduct->name,
            'description' => $masterProduct->description,
            'article_number' => $masterProduct->article_number,
            'status' => $masterProduct->status === Statuses::ACTIVE->value ? (int) true : (int) false,
            'brand_id' => $this->getExternalBrandId($masterProduct->brand_id, $saleChannel->id),
            'category_ids' => $this->getExternalCategoryIds($saleChannel, $masterProduct),
            'images' => array_column($masterProduct->getDiskBasedMediaUrls('images'), 'url'),
            'videos' => array_column($masterProduct->getDiskBasedMediaUrls('videos'), 'url'),
            'thumbnail' => $masterProduct->getDiskBasedFirstMediaUrl('thumbnail'),
        ];
    }

    private function getExternalBrandId(int $brandId, int $saleChannelId): int
    {
        $brandChannelReferenceQueries = resolve(BrandChannelReferenceQueries::class);

        /** @var BrandChannelReference $brandChannelReference */
        $brandChannelReference = $brandChannelReferenceQueries->getByBrandIdAndSaleChannelId(
            $brandId,
            $saleChannelId
        );

        return $brandChannelReference->external_brand_id;
    }

    private function getExternalCategoryIds(SaleChannel $saleChannel, MasterProduct $masterProduct): array
    {
        $categoryIds = $masterProduct->categories->pluck('id')->toArray();

        $categoryChannelReferenceQueries = resolve(CategoryChannelReferenceQueries::class);

        return $categoryChannelReferenceQueries->getBySaleChannelIdCategoryIds(
            $categoryIds,
            $saleChannel->id
        )->pluck('external_category_id')->toArray();
    }

    private function transformAttributes(Collection $attributes): Collection
    {
        return $attributes->map(function ($attribute): array {
            $customFieldValue = $attribute->customFieldValue ? $attribute->customFieldValue->value : $attribute->default_value;

            return [
                'id' => $attribute->id,
                'name' => $attribute->name,
                'field_type' => $attribute->field_type,
                'is_required' => $attribute->is_required,
                'from' => in_array(
                    $attribute->field_type,
                    FieldType::allowFromToFunctionalityFields()
                ) ? $attribute->from : null,
                'to' => in_array(
                    $attribute->field_type,
                    FieldType::allowFromToFunctionalityFields()
                ) ? $attribute->to : null,
                'options' => in_array($attribute->field_type, FieldType::selections()) ? $attribute->options : null,
                'selected_value' => FieldType::prepareValueByFieldType($attribute->field_type, $customFieldValue),
            ];
        });
    }
}
