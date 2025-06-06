<?php

declare(strict_types=1);

namespace App\Domains\Product\Services;

use App\CommonFunctions;
use App\Domains\Attribute\Enums\FieldType;
use App\Domains\BoxProduct\BoxProductQueries;
use App\Domains\Category\CategoryQueries;
use App\Domains\Color\ColorQueries;
use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\Common\Services\ExportService;
use App\Domains\Company\CompanyQueries;
use App\Domains\Company\Enums\CommissionTypes;
use App\Domains\Department\DepartmentQueries;
use App\Domains\ExportRecord\Enums\ExportRecordTypes;
use App\Domains\ExportRecord\ExportRecordQueries;
use App\Domains\ExportRecord\Jobs\ExportToExcelJob;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\MasterProduct\MasterProductQueries;
use App\Domains\Membership\MembershipQueries;
use App\Domains\PackageType\PackageTypeQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\Permission\Services\PermissionModuleService;
use App\Domains\Product\DataObjects\ProductArticleData;
use App\Domains\Product\DataObjects\ProductData;
use App\Domains\Product\DataObjects\ProductStockPurchasePlanData;
use App\Domains\Product\DataObjects\ProductWithLocationStockData;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Product\Enums\Statuses;
use App\Domains\Product\Exports\BoxProductExport;
use App\Domains\Product\Exports\BulkUpdateProductExport;
use App\Domains\Product\Exports\LoyaltyPointProductExport;
use App\Domains\Product\ProductQueries;
use App\Domains\Product\SubDepartment\Enums\SubDepartments;
use App\Domains\ProductLoyaltyPoint\ProductLoyaltyPointQueries;
use App\Domains\Promotion\PromotionQueries;
use App\Domains\PurchaseOrder\Services\PurchaseOrderService;
use App\Domains\PurchasePlan\Services\PurchasePlanService;
use App\Domains\RetailPlanningHierarchy\RetailPlanningHierarchyQueries;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Domains\Season\SeasonQueries;
use App\Domains\Size\SizeQueries;
use App\Domains\Style\StyleQueries;
use App\Domains\Tag\TagQueries;
use App\Domains\Template\TemplateQueries;
use App\Domains\UnitOfMeasure\UnitOfMeasureQueries;
use App\Domains\Vendor\VendorQueries;
use App\Exceptions\RedirectBackWithErrorException;
use App\Models\Admin;
use App\Models\Brand;
use App\Models\Color;
use App\Models\Department;
use App\Models\MasterProduct;
use App\Models\Product;
use App\Models\ProductVariantValue;
use App\Models\SaleChannel;
use App\Models\Season;
use App\Models\Size;
use App\Models\StoreManager;
use App\Models\Style;
use App\Models\UnitOfMeasure;
use App\Models\UnitOfMeasureDerivative;
use App\Models\Vendor;
use App\Models\WarehouseManager;
use App\Services\RetailPlanningService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ProductService
{
    /**
     * @return array<string, mixed>
     */
    public function getActiveInventoryProductDetailsForArticleNumber(
        ProductArticleData $productArticleData,
        int $companyId
    ): array {
        if (config('app.product_variant')) {
            return $this->getActiveInventoryVariantProductDetails($productArticleData, $companyId);
        }

        return $this->getStandardActiveInventoryProductDetailsForArticleNumber($productArticleData, $companyId);
    }

    public function productDataPrint(Collection $productDetails, Collection $filteredColumns): Collection
    {
        return $productDetails->map(function ($product) use ($filteredColumns): array {
            /** @var Carbon $createdAt */
            $createdAt = $product->created_at;

            /** @var Carbon $updatedAt */
            $updatedAt = $product->updated_at;

            [$category, $parentSubcategory, $subSubcategories] = $this->getProductCategories($product);

            /** @var int ?$typeId */
            $typeId = config('app.product_variant') ? $product->masterProduct?->type_id : $product->type_id;

            $productData = [
                'name' => $product->name,
                'code' => $product->code ?? 'N/A',
                'brand' => $this->getBrand($product),
                'color' => config('app.product_variant') ? null : $product->color?->getName(),
                'size' => config('app.product_variant') ? null : $product->size?->getName(),
                'style' => config('app.product_variant') ? null : $product?->style?->getName() ?? 'N/A',
                'attributes' => $this->getAttributesForPrint($product),
                'categories' => $category ? $category->name : null,
                'upc' => $product->upc ?? 'N/A',
                'article_number' => $this->getArticleNumber($product),
                'retail_price' => CommonFunctions::currencyFormat((float) $product->retail_price),
                'original_created_at' => $product->original_created_at ?: 'N/A',
                'created_by' => $product->createdBy?->employee->staff_id,
                'approved_by' => $product->draftProductTransaction?->approvedBy?->employee->staff_id,
                'last_editor_by' => $product->lastEditorBy?->employee->staff_id,
                'created_at' => $createdAt->format('d-m-Y h:i:s A'),
                'updated_at' => $updatedAt->format('d-m-Y h:i:s A'),
                'custom_sku' => $product->custom_sku ?: 'N/A',
                'ean' => $product->ean ?: 'N/A',
                'manufacturer_sku' => $product->manufacturer_sku ?: 'N/A',
                'department' => $this->getDepartment($product),
                'sub_department' => $product->getSubDepartmentId() ? SubDepartments::getFormattedCaseName(
                    $product->getSubDepartmentId()
                ) : 'N/A',
                'type' => $typeId ? ProductTypes::getFormattedCaseName($typeId) : 'N/A',
                'external_reference' => $product->productChannelReference ? 'External Product Id: ' . $product->productChannelReference->external_product_id : 'N/A',
                'external_variant' => $product->productChannelReference ? 'External Variant Id: ' . $product->productChannelReference->external_variant_id : 'N/A',
            ];

            $exportService = resolve(ExportService::class);

            return $exportService->exportData($productData, $filteredColumns);
        });
    }

    public function getProductDetailsByArticleNumber(array $filterData, int $companyId): array
    {
        if (config('app.product_variant')) {
            return $this->getVariantProductDetails($filterData['article_number'], $companyId);
        }

        return $this->getStandardProductDetails($filterData['article_number'], $companyId);
    }

    private function getStandardProductDetails(string $articleNumber, int $companyId): array
    {
        $productQueries = resolve(ProductQueries::class);
        $products = $productQueries->searchByArticleNumber($articleNumber, $companyId);

        $colors = $products->sortBy('color.name')->pluck('color.name')->unique()->filter()->toArray();
        $sizes = $products->sortBy('size.sort_order')->pluck('size.name')->unique()->filter()->toArray();

        $products = $products->map(function ($product): array {
            /** @var ?Size $size */
            $size = $product->size?->name;
            /** @var ?Color $color */
            $color = $product->color?->name;

            return [
                'id' => $product->id,
                'has_batch' => $product->has_batch,
                'color' => $product->color,
                'size' => $product->size,
                'stock' => null,
                'combination' => $size . ' ' . $color,
                'name' => $product->name,
                'compound_product_name' => $product->compound_product_name,
            ];
        });

        return [
            'products' => $products,
            'xNames' => $sizes,
            'yNames' => $colors,
        ];
    }

    private function getVariantProductDetails(string $articleNumber, int $companyId): array
    {
        $masterProductQueries = resolve(MasterProductQueries::class);
        $masterProduct = $masterProductQueries->searchByArticleNumber($articleNumber, $companyId);

        if (! $masterProduct) {
            return $this->emptyProductResponse();
        }

        $products = $this->mapProductVariants($masterProduct);
        $attributeNames = $products->pluck('attribute_names')->unique()->filter()->toArray();
        $variantValues = $products->pluck('variant_values')->unique()->filter()->toArray();

        if (count($attributeNames[0]) < 2) {
            return $this->emptyProductResponse();
        }

        [$xNames, $yNames] = $this->splitVariantValues($variantValues, count($attributeNames[0]));

        $xFormatNames = $this->formatVariantNames($xNames);
        $yFormatNames = $this->formatVariantNames($yNames);

        return [
            'products' => $products,
            'attributeNames' => $attributeNames,
            'xNames' => $xFormatNames,
            'yNames' => $yFormatNames,
        ];
    }

    private function mapProductVariants(MasterProduct $masterProduct): Collection
    {
        return $masterProduct->productVariants->map(function ($product) use ($masterProduct): array {
            $attributeNames = $product->productVariantValues->pluck('attribute.name')->toArray();
            $variantValues = $product->productVariantValues->pluck('value')->toArray();
            $combined = array_combine($attributeNames, $variantValues);

            return [
                'id' => $product->id,
                'has_batch' => $masterProduct->has_batch,
                'attribute_names' => $attributeNames,
                'variant_values' => $variantValues,
                'stock' => null,
                'combination' => implode(' ', $variantValues),
                'name' => $product->name,
                'compound_product_name' => $product->compound_product_name,
                'color' => array_key_exists('color', $combined) ? $combined['color'] : 'N/A',
                'size' => array_key_exists('size', $combined) ? $combined['size'] : 'N/A',
            ];
        });
    }

    private function splitVariantValues(array $variantValues, int $totalAttributes): array
    {
        $x = (int) ceil($totalAttributes / 2);
        $y = $totalAttributes - $x;

        $xNames = array_map(fn ($item): array => array_slice($item, 0, $x), $variantValues);
        $yNames = array_map(fn ($item): array => array_slice($item, $x, $y), $variantValues);

        return [collect($xNames)->unique()->toArray(), collect($yNames)->unique()->toArray()];
    }

    private function formatVariantNames(array $variantNames): array
    {
        return array_map(fn ($names): string => implode('|', $names), $variantNames);
    }

    private function emptyProductResponse(): array
    {
        return [
            'products' => [],
            'attributeNames' => [],
            'xNames' => [],
            'yNames' => [],
        ];
    }

    public function getProductArticleNumberWithLocationStock(
        ProductWithLocationStockData $productWithLocationStockData,
        int $companyId
    ): array {
        if (config('app.product_variant')) {
            return $this->getVariantProductArticleNumberWithLocationStock($productWithLocationStockData, $companyId);
        }

        return $this->getStandardProductArticleNumberWithLocationStock($productWithLocationStockData, $companyId);
    }

    public function getProductArticleNumberWithLocationStockForPurchasePlan(
        ProductStockPurchasePlanData $productStockPurchasePlanData,
        int $companyId
    ): array {
        $productQueries = resolve(ProductQueries::class);
        $products = $productQueries->searchByArticleNumberWithDerivatives(
            $productStockPurchasePlanData->article_number,
            $companyId
        );

        $purchasePlanService = resolve(PurchasePlanService::class);
        $locationInventories = $purchasePlanService->getLocationStock(
            $products->pluck('id')->toArray(),
            $productStockPurchasePlanData->location_id,
        );

        $colors = $products->sortBy('color.name')->pluck('color.name')->unique()->filter()->toArray();
        $sizes = $products->sortBy('size.sort_order')->pluck('size.name')->unique()->filter()->toArray();

        $products = $products->map(function ($product) use ($locationInventories): array {
            /** @var ?Size $size */
            $size = $product->size?->name;
            /** @var ?Color $color */
            $color = $product->color?->name;
            /** @var ?UnitOfMeasure $unitOfMeasure */
            $unitOfMeasure = $product->unitOfMeasure;
            /** @var ?UnitOfMeasureDerivative $derivatives */
            $derivatives = $unitOfMeasure?->derivatives;
            $inventory = collect($locationInventories)->where('product_id', $product->id)->first();

            return [
                'id' => $product->id,
                'has_batch' => $product->has_batch,
                'color' => $product->color,
                'size' => $product->size,
                'unit_of_measure' => $unitOfMeasure,
                'derivatives' => $derivatives,
                'combination' => $color . ' ' . $size,
                'name' => $product->name,
                'compound_product_name' => $product->compound_product_name,
                'stock' => $inventory ? (int) $inventory['stock'] : 0,
                'reserved_stock' => $inventory ? (int) $inventory['reserved_stock'] : 0,
            ];
        });

        return [
            'products' => $products,
            'colors' => $colors,
            'sizes' => $sizes,
        ];
    }

    public function checkRequestDetails(int $brandId, int $companyId, ProductData $productData): void
    {
        $companyQueries = resolve(CompanyQueries::class);
        $this->validateTypeIdAndMinimumPrice((int) $productData->type_id, $productData->minimum_price);

        $hasAllBrandsAttachedInCompany = $companyQueries->hasAllBrandsAttached($companyId, [$brandId]);

        if (! $hasAllBrandsAttachedInCompany) {
            throw new RedirectBackWithErrorException('The selected brand does not match with the current company.');
        }

        if (null !== $productData->sale_channel_ids) {
            $saleChannelQueries = resolve(SaleChannelQueries::class);

            $allSaleChannelExist = $saleChannelQueries->doAllSaleChannelExist(
                $companyId,
                $productData->sale_channel_ids
            );

            if (! $allSaleChannelExist) {
                throw new RedirectBackWithErrorException(
                    'One of the selected sale channel does not match the current company.'
                );
            }
        }
    }

    public function validateBoxProductLoyaltyPointMembership(ProductData $productData): void
    {
        if (! $productData->boxes) {
            return;
        }

        foreach ($productData->boxes as $box) {
            if (! array_key_exists('box_product_loyalty_points', $box)) {
                continue;
            }

            /** @var array $boxProductLoyaltyPointsArray */
            $boxProductLoyaltyPointsArray = $box['box_product_loyalty_points'];

            $boxProductLoyaltyPoints = collect($boxProductLoyaltyPointsArray);
            if (
                $boxProductLoyaltyPoints->count()
                !== $boxProductLoyaltyPoints->pluck('membership_id')->unique()->count()
            ) {
                throw new RedirectBackWithErrorException('Box Product Membership field is duplicate values.');
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function getCommonRecords(int $companyId): array
    {
        $unitOfMeasureQueries = resolve(UnitOfMeasureQueries::class);
        $seasonQueries = resolve(SeasonQueries::class);
        $departmentQueries = resolve(DepartmentQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $styleQueries = resolve(StyleQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);
        $tagQueries = resolve(TagQueries::class);
        $membershipQueries = resolve(MembershipQueries::class);
        $company = $companyQueries->getByIdWithPromoterCommissionDetails($companyId);
        $packageTypeQueries = resolve(PackageTypeQueries::class);
        $templateQueries = resolve(TemplateQueries::class);
        $retailPlanningService = resolve(RetailPlanningService::class);
        $vendorQueries = resolve(VendorQueries::class);
        $saleChannelQueries = resolve(SaleChannelQueries::class);
        $saleChannels = $saleChannelQueries->getAllByCompanyId($companyId);

        $retailPlanningServiceConfigured = $retailPlanningService->isConfigured();

        $retailData = [];
        if ($retailPlanningServiceConfigured) {
            $retailPlanningHierarchyQueries = resolve(RetailPlanningHierarchyQueries::class);
            $retailData['retailPlanningHierarchies'] = $retailPlanningHierarchyQueries->getTopLevelHierarchies(
                $companyId
            );
        }

        return [
            'unitOfMeasures' => $unitOfMeasureQueries->getWithBasicColumns($companyId),
            'seasons' => $seasonQueries->getWithBasicColumns($companyId),
            'departments' => $departmentQueries->getWithBasicColumns($companyId),
            'subDepartments' => SubDepartments::formattedForSelection(),
            'colors' => $colorQueries->getWithBasicColumns($companyId),
            'sizes' => $sizeQueries->getWithBasicColumns($companyId),
            'brands' => $companyQueries->getByIdWithBrands($companyId)->brands,
            'styles' => $styleQueries->getWithBasicColumns($companyId),
            'categories' => $categoryQueries->getMainCategoriesWithBasicColumns($companyId),
            'vendors' => $vendorQueries->getWithBasicColumns($companyId),
            'tags' => $tagQueries->getWithBasicColumns($companyId),
            'types' => ProductTypes::formattedForSelection(),
            'memberships' => $membershipQueries->getWithBasicColumns($companyId),
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
                'serialProduct' => ProductTypes::SERIAL_PRODUCT->value,
            ],
            'packageTypes' => $packageTypeQueries->getWithBasicColumns($companyId),
            'templates' => $templateQueries->fetchForDropdown($companyId),
            'fieldTypes' => FieldType::getFormattedArrayForStaticUse(),
            'retailPlanningServiceConfigured' => $retailPlanningServiceConfigured,
            ...$retailData,
            'saleChannels' => $saleChannels,
        ];
    }

    public function validateRetailPriceForPromotion(ProductData $productData, int $productId, int $companyId): void
    {
        $productQueries = resolve(ProductQueries::class);
        $product = $productQueries->getById($productId, $companyId);

        if ((float) $product->retail_price === $productData->retail_price) {
            return;
        }

        $promotionQueries = resolve(PromotionQueries::class);
        if (! $promotionQueries->promotionExistsForProduct($productId, $companyId)) {
            return;
        }

        throw new RedirectBackWithErrorException(
            "The product hasn't been updated because it's part of a promotion offering a choice between a flat discount or a percentage discount on the next item."
        );
    }

    private function validateTypeIdAndMinimumPrice(int $typeId, ?float $minimumPrice): void
    {
        if ($typeId === ProductTypes::REGULAR_PRODUCT->value || $typeId === ProductTypes::ASSEMBLY_PRODUCT->value || $typeId === ProductTypes::SERIAL_PRODUCT->value) {
            return;
        }

        if ($minimumPrice && 0.0 !== $minimumPrice) {
            return;
        }

        throw new RedirectBackWithErrorException(
            'To proceed, a minimum price needs to be established for non-standard products.'
        );
    }

    /**
     * @return mixed[]
     */
    private function getProductCategories(Product $product): array
    {
        $categories = config('app.product_variant')
        ? $product->masterProduct?->categories
            : $product->categories;

        $category = $categories?->first();
        $parentSubcategory = $categories?->firstWhere('pivot.sort_order', 1);
        $subSubcategories = $categories?->where('pivot.sort_order', '>=', 2) ?? collect([]);

        return [$category, $parentSubcategory, $subSubcategories];
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

    public function getVendorCommissionPercentages(Collection $products): array
    {
        $vendorCommissionPercentages = [];

        foreach ($products as $product) {
            /** @var ?Vendor $vendor */
            $vendor = $product->vendor;

            if ($vendor instanceof Vendor) {
                $vendorCommissionPercentages[$product->id] = $vendor->commission_percentage;

                continue;
            }

            $vendorCommissionPercentages[$product->id] = null;
        }

        return $vendorCommissionPercentages;
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

    public function exportProductWithJob(
        Admin|StoreManager|WarehouseManager $user,
        array $filterData,
        int $companyId,
        Collection $columns
    ): array {
        $productQueries = resolve(ProductQueries::class);
        $totalRecords = $productQueries->getProductsExportCount($filterData, $companyId);

        if ($totalRecords <= (int) config('app.excel.export.job_limit')) {
            return [
                'exceeds_limit' => false,
            ];
        }

        $exportRecordQueries = resolve(ExportRecordQueries::class);

        $headerColumns = $columns->toArray();

        $exportRecord = $exportRecordQueries->addNew(
            $user,
            $filterData,
            $companyId,
            ExportRecordTypes::PRODUCTS->value,
            $headerColumns,
            $totalRecords
        );

        ExportToExcelJob::dispatch($exportRecord)->onQueue('medium');

        return [
            'exceeds_limit' => true,
            'message' => 'Your export request is being processed in the background. You can track its progress in the Export Record module.',
        ];
    }

    public function exportProductLoyaltyPointWithJob(
        Admin|StoreManager|WarehouseManager $user,
        array $filterData,
        int $companyId
    ): array {
        $productLoyaltyPointQueries = resolve(ProductLoyaltyPointQueries::class);
        $totalRecords = $productLoyaltyPointQueries->getLoyaltyPointProductsExportCount($filterData, $companyId);

        if ($totalRecords <= (int) config('app.excel.export.job_limit')) {
            return [
                'exceeds_limit' => false,
            ];
        }

        $exportRecordQueries = resolve(ExportRecordQueries::class);

        $headerColumns = (new LoyaltyPointProductExport(collect([])))->headings();

        $exportRecord = $exportRecordQueries->addNew(
            $user,
            $filterData,
            $companyId,
            ExportRecordTypes::PRODUCT_LOYALTY_POINTS->value,
            $headerColumns,
            $totalRecords
        );

        ExportToExcelJob::dispatch($exportRecord)->onQueue('medium');

        return [
            'exceeds_limit' => true,
            'message' => 'Your export request is being processed in the background. You can track its progress in the Export Record module.',
        ];
    }

    public function exportBoxProductWithJob(
        Admin|StoreManager|WarehouseManager $user,
        array $filterData,
        int $companyId
    ): array {
        $boxProductQueries = resolve(BoxProductQueries::class);
        $totalRecords = $boxProductQueries->getBoxProductsExportCount($filterData, $companyId);

        if ($totalRecords <= (int) config('app.excel.export.job_limit')) {
            return [
                'exceeds_limit' => false,
            ];
        }

        $exportRecordQueries = resolve(ExportRecordQueries::class);

        $headerColumns = (new BoxProductExport(collect([])))->headings();

        $exportRecord = $exportRecordQueries->addNew(
            $user,
            $filterData,
            $companyId,
            ExportRecordTypes::PRODUCT_BOXES->value,
            $headerColumns,
            $totalRecords
        );

        ExportToExcelJob::dispatch($exportRecord)->onQueue('medium');

        return [
            'exceeds_limit' => true,
            'message' => 'Your export request is being processed in the background. You can track its progress in the Export Record module.',
        ];
    }

    public function exportProductWithJobForImportBulkUpdate(
        Admin|StoreManager|WarehouseManager $user,
        array $filterData,
        int $companyId
    ): array {
        $productQueries = resolve(ProductQueries::class);
        $totalRecords = $productQueries->getProductsExportCount($filterData, $companyId);

        if ($totalRecords <= (int) config('app.excel.export.job_limit')) {
            return [
                'exceeds_limit' => false,
            ];
        }

        $exportRecordQueries = resolve(ExportRecordQueries::class);

        $headerColumns = (new BulkUpdateProductExport(collect([]), $filterData['all_permission_lists']))->headings();

        $exportRecord = $exportRecordQueries->addNew(
            $user,
            $filterData,
            $companyId,
            ExportRecordTypes::PRODUCT_EXPORT_FOR_IMPORT_BULK_UPDATE->value,
            $headerColumns,
            $totalRecords
        );

        ExportToExcelJob::dispatch($exportRecord)->onQueue('medium');

        return [
            'exceeds_limit' => true,
            'message' => 'Your export request is being processed in the background. You can track its progress in the Export Record module.',
        ];
    }

    public function preparedProductRecords(Collection $products, Collection $filteredColumns,
        array $allPermissionLists = []): Collection
    {
        return $products->map(function (Product $product) use ($allPermissionLists, $filteredColumns): array {
            /** @var ?Vendor $vendor */
            $vendor = $product->vendor;

            /** @var ?Season $season */
            $season = $product->season;

            /** @var Collection $saleChannels */
            $saleChannels = $product->saleChannels;

            /** @var Carbon $updatedAt */
            $updatedAt = $product->updated_at;

            /** @var Carbon $createdAt */
            $createdAt = $product->created_at;

            [$category, $parentSubcategory, $subSubcategories] = $this->getProductCategories($product);

            $optionalColumns = $this->getOptionalPermissionColumns($allPermissionLists);

            /** @var int ?$typeId */
            $typeId = config('app.product_variant') ? $product->masterProduct?->type_id : $product->type_id;

            $productDetails = [
                'name' => $product->name,
                'code' => $product->code,
                'brand' => $this->getBrand($product),
                'color' => config('app.product_variant') ? null : $product->color?->getName(),
                'size' => config('app.product_variant') ? null : $product->size?->getName(),
                'style' => config('app.product_variant') ? null : $product->style?->getName(),
                'attributes' => $this->getAttributesForPrint($product),
                'department' => $this->getDepartment($product),
                'categories' => $category ? $category->name : null,
                'upc' => $product->upc,
                'article_number' => $this->getArticleNumber($product),
                'retail_price' => (float) $product->retail_price,
                'original_created_at' => $this->getOriginalCreatedAt($product),
                'created_by' => $product->createdBy?->employee->staff_id,
                'approved_by' => $product->draftProductTransaction?->approvedBy?->employee->staff_id,
                'last_editor_by' => $product->lastEditorBy?->employee->staff_id,
                'created_at' => $product->created_at,
                'updated_at' => $updatedAt->format('d-m-Y h:i:s A'),
                'is_temporarily_unavailable' => $product->is_temporarily_unavailable ? 'Yes' : 'No',
                'ean' => $product->ean,
                'custom_sku' => $product->custom_sku,
                'manufacturer_sku' => $product->manufacturer_sku,
                'type_id' => $typeId ? ProductTypes::getFormattedCaseName($typeId) : 'N/A',
                'franchise_price_1' => (float) $product->franchise_price_1,
                'franchise_price_2' => (float) $product->franchise_price_2,
                'franchise_price_3' => (float) $product->franchise_price_3,
                'wholesale_price' => (float) $product->wholesale_price,
                'company_or_tender_price' => (float) $product->company_or_tender_price,
                'branch_price' => (float) $product->branch_price,
                'minimum_price' => (float) $product->minimum_price,
                'original_capital_price' => (float) $product->original_capital_price,
                'capital_price' => (float) $product->capital_price,
                'staff_price' => (float) $product->staff_price,
                'purchase_cost' => (float) $product->purchase_cost,
                'online_price' => (float) $product->online_price,
                'subcategory_name' => $parentSubcategory ? $parentSubcategory->name : null,
                'subsubcategory_name' => $subSubcategories ? $subSubcategories->implode('name', ' > ') : null,
                'has_batch' => $this->getHasBatch($product) ? 'Yes' : 'No',
                'is_non_inventory' => $this->getNonInventory($product) ? 'Yes' : 'No',
                'is_non_selling_item' => $this->getNonSelling($product) ? 'Yes' : 'No',
                'is_available_in_pos' => $product->is_available_in_pos ? 'Yes' : 'No',
                'is_available_in_ecommerce' => $product->is_available_in_ecommerce ? 'Yes' : 'No',
                'is_sold_as_single_item' => $product->is_sold_as_single_item ? 'Yes' : 'No',
                'sell_item_via_derivative' => $product->sell_item_via_derivative ? 'Yes' : 'No',
                'tags' => $this->getTags($product),
                'vendor' => $vendor instanceof Vendor ? $vendor->name : 'N/A',
                'sale_channels' => implode(',', $this->getProductSaleChannels($saleChannels)),
                'unit_of_measure' => config(
                    'app.product_variant'
                ) ? $product->masterProduct?->unitOfMeasure?->getName() ?? 'N/A' : $product->unitOfMeasure?->getName() ?? 'N/A',
                'description' => $product->description ?: 'N/A',
                'season' => $season instanceof Season ? $season->getName() : 'N/A',
                'sub_department' => $product->getSubDepartmentId() ? SubDepartments::getFormattedCaseName(
                    $product->getSubDepartmentId()
                ) : 'N/A',
                'status' => $this->getStatus($product->status),
                'external_reference' => $product->productChannelReference ? 'External Product Id: ' . $product->productChannelReference->external_product_id : 'N/A',
                'external_variant' => $product->productChannelReference ? 'External Variant Id: ' . $product->productChannelReference->external_variant_id : 'N/A',
            ];

            $data = array_diff_key($productDetails, array_flip($optionalColumns));

            $exportService = resolve(ExportService::class);

            return $exportService->exportData($data, $filteredColumns);
        });
    }

    public function preparedProductRecordsForBulkUpdate(
        Collection $products,
        array $allPermissionLists = []
    ): Collection {
        return $products->map(function (Product $product) use ($allPermissionLists): array {
            /** @var Brand $brand */
            $brand = $product->brand;

            /** @var ?Color $color */
            $color = $product->color;

            /** @var ?Vendor $vendor */
            $vendor = $product->vendor;

            /** @var ?Size $size */
            $size = $product->size;

            /** @var ?UnitOfMeasure $unitOfMeasure */
            $unitOfMeasure = $product->unitOfMeasure;

            /** @var ?Season $season */
            $season = $product->season;

            /** @var ?Department $department */
            $department = $product->department;

            /** @var ?Style $style */
            $style = $product->style;

            /** @var Collection $saleChannels */
            $saleChannels = $product->saleChannels;

            [$category, $parentSubcategory, $subSubcategories] = $this->getProductCategories($product);

            $optionalColumns = $this->getOptionalPermissionColumns($allPermissionLists);

            /** @var int $typeId */
            $typeId = config('app.product_variant') ? $product->masterProduct?->type_id : $product->type_id;

            $productDetails = [
                'name' => $product->name,
                'description' => $product->description,
                'code' => $product->code,
                'unit_of_measure' => $unitOfMeasure instanceof UnitOfMeasure ? $unitOfMeasure->getName() : null,
                'season' => $season instanceof Season ? $season->getName() : null,
                'department' => $department instanceof Department ? $department->getName() : null,
                'sub_department' => $product->getSubDepartmentId() ? SubDepartments::getFormattedCaseName(
                    $product->getSubDepartmentId()
                ) : null,
                'color' => $color instanceof Color ? $color->getName() : null,
                'size' => $size instanceof Size ? $size->getName() : null,
                'style' => $style instanceof Style ? $style->getName() : null,
                'is_temporarily_unavailable' => $product->is_temporarily_unavailable ? 'Yes' : 'No',
                'upc' => $product->upc,
                'verification_qr_code' => $product->verification_qr_code,
                'ean' => $product->ean,
                'custom_sku' => $product->custom_sku,
                'manufacturer_sku' => $product->manufacturer_sku,
                'brand' => $brand->name,
                'type_id' => ProductTypes::getFormattedCaseName($typeId),
                'retail_price' => (float) $product->retail_price,
                'franchise_price_1' => (float) $product->franchise_price_1,
                'franchise_price_2' => (float) $product->franchise_price_2,
                'franchise_price_3' => (float) $product->franchise_price_3,
                'wholesale_price' => (float) $product->wholesale_price,
                'company_or_tender_price' => (float) $product->company_or_tender_price,
                'branch_price' => (float) $product->branch_price,
                'minimum_price' => (float) $product->minimum_price,
                'original_capital_price' => (float) $product->original_capital_price,
                'capital_price' => (float) $product->capital_price,
                'staff_price' => (float) $product->staff_price,
                'purchase_cost' => (float) $product->purchase_cost,
                'online_price' => (float) $product->online_price,
                'article_number' => $this->getArticleNumber($product),
                'category_name' => $category ? $category->name : null,
                'subcategory_name' => $parentSubcategory ? $parentSubcategory->name : null,
                'subsubcategory_name' => $subSubcategories ? $subSubcategories->implode('name', ' > ') : null,
                'has_batch' => $product->has_batch ? 'Yes' : 'No',
                'is_non_inventory' => $product->is_non_inventory ? 'Yes' : 'No',
                'is_non_selling_item' => $product->is_non_selling_item ? 'Yes' : 'No',
                'is_available_in_pos' => $product->is_available_in_pos ? 'Yes' : 'No',
                'is_available_in_ecommerce' => $product->is_available_in_ecommerce ? 'Yes' : 'No',
                'is_sold_as_single_item' => $product->is_sold_as_single_item ? 'Yes' : 'No',
                'sell_item_via_derivative' => $product->sell_item_via_derivative ? 'Yes' : 'No',
                'status' => $this->getStatus($product->status),
                'tags' => $this->getTags($product),
                'vendor' => $vendor instanceof Vendor ? $vendor->name : null,
                'sale_channels' => implode(',', $this->getProductSaleChannels($saleChannels)),
                'original_created_at' => $product->original_created_at,
                'created_at' => $product->created_at,
            ];

            return array_diff_key($productDetails, array_flip($optionalColumns));
        });
    }

    public function getColorAndSize(Product $product): array
    {
        if (config('app.product_variant')) {
            $size = $product->productVariantValues->map(
                fn ($value) => strcasecmp((string) $value->attribute?->name, 'size') === 0 ? $value : null
            )->filter()->first();

            $color = $product->productVariantValues->map(
                fn ($value) => strcasecmp((string) $value->attribute?->name, 'color') === 0 ? $value : null
            )->filter()->first();

            $size = $size instanceof ProductVariantValue ? $size->value : 'N/A';
            $color = $color instanceof ProductVariantValue ? $color->value : 'N/A';

            return [$color, $size];
        }

        /** @var ?Size $size */
        $size = $product->size;
        /** @var ?Color $color */
        $color = $product->color;

        $color = $color instanceof Color ? $color->getName() : 'N/A';

        $size = $size instanceof Size ? $size->getName() : 'N/A';

        return [$color, $size];
    }

    public function filterColumnsForPdf(Collection $filteredColumns): array
    {
        return $filteredColumns
            ->reject(fn ($column): bool => in_array($column, $this->rejectColumns()))
            ->toArray();
    }

    private function rejectColumns(): array
    {
        $rejectedColumns = [
            'images',
            'thumbnail_url',
            'updated_at',
            'is_temporarily_unavailable',
            'franchise_price_1',
            'franchise_price_2',
            'franchise_price_3',
            'wholesale_price',
            'company_or_tender_price',
            'branch_price',
            'minimum_price',
            'original_capital_price',
            'capital_price',
            'staff_price',
            'purchase_cost',
            'online_price',
            'subcategory_name',
            'subsubcategory_name',
            'has_batch',
            'is_non_inventory',
            'is_non_selling_item',
            'is_available_in_pos',
            'is_available_in_ecommerce',
            'is_sold_as_single_item',
            'sell_item_via_derivative',
            'tags',
            'vendor',
            'sale_channels',
            'unit_of_measure',
            'description',
            'season',
            'sub_department',
            'status',
            'type_id',
            'action',
        ];

        if (! config('app.product_variant')) {
            $rejectedColumns[] = 'attributes';
        }

        return $rejectedColumns;
    }

    private function getStatus(int $status): string
    {
        if ($status === Statuses::ACTIVE->value) {
            return Statuses::getCaseName(Statuses::ACTIVE->value);
        }

        if ($status === Statuses::ARCHIVED->value) {
            return Statuses::getCaseName(Statuses::ARCHIVED->value);
        }

        return '';
    }

    private function getOptionalPermissionColumns(array $allPermissionLists): array
    {
        $assignedPermissions = array_map(
            fn ($value): string => 'product_' . $value,
            PermissionModuleService::getModuleSubLists()['Product']
        );

        return array_map(
            fn ($value): string => Str::replace('product_', '', $value),
            array_diff($assignedPermissions, $allPermissionLists)
        );
    }

    private function getBrand(Product $product): ?string
    {
        return config(
            'app.product_variant'
        ) ? $product->masterProduct?->brand?->getName() : $product->brand?->getName();
    }

    private function getArticleNumber(Product $product): ?string
    {
        return config('app.product_variant') ? $product->masterProduct?->article_number : $product->article_number;
    }

    private function getHasBatch(Product $product): ?bool
    {
        return config('app.product_variant') ? $product->masterProduct?->has_batch : $product->has_batch;
    }

    private function getNonInventory(Product $product): ?bool
    {
        return config('app.product_variant') ? $product->masterProduct?->is_non_inventory : $product->is_non_inventory;
    }

    private function getNonSelling(Product $product): ?bool
    {
        return config(
            'app.product_variant'
        ) ? $product->masterProduct?->is_non_selling_item : $product->is_non_selling_item;
    }

    private function getDepartment(Product $product): ?string
    {
        return config(
            'app.product_variant'
        ) ? $product->masterProduct?->department?->getName() : $product->department?->getName();
    }

    private function getTags(Product $product): ?string
    {
        $tags = config('app.product_variant')
        ? $product->masterProduct?->tags
            : $product->tags;

        return $tags?->pluck('name')->implode(', ');
    }

    private function getProductSaleChannels(Collection $saleChannels): array
    {
        return $saleChannels->map(function ($saleChannel): string {
            /** @var SaleChannel $productSaleChannel */
            $productSaleChannel = $saleChannel;

            return $productSaleChannel->getName();
        })->toArray();
    }

    private function getOriginalCreatedAt(Product $product): string
    {
        if ($product->original_created_at) {
            /** @var Carbon $originalCreatedAt */
            $originalCreatedAt = Carbon::createFromFormat('Y-m-d H:i:s', $product->original_created_at);

            return $originalCreatedAt->format('d-m-Y h:i:s A');
        }

        return 'N/A';
    }

    public function getAttributesForPrint(Product $product): string
    {
        $attributes = [];
        if (config('app.product_variant')) {
            foreach ($product->productVariantValues as $productVariantValue) {
                $attributes[] = $productVariantValue->attribute?->name .' : '.$productVariantValue->value;
            }
        }

        return implode(', ', $attributes);
    }

    public function getAttributesValueForPrint(Product $product): string
    {
        $attributes = [];
        if (config('app.product_variant')) {
            foreach ($product->productVariantValues as $productVariantValue) {
                $attributes[] = $productVariantValue->value;
            }
        }

        return implode(' ', $attributes);
    }

    public function getAttributesArray(Product $product): array
    {
        $attributes = [];
        if (config('app.product_variant')) {
            foreach ($product->productVariantValues as $productVariantValue) {
                $attributes[$productVariantValue->attribute?->name] = $productVariantValue->value;
            }
        }

        return $attributes;
    }

    public function getAttributesArrayForApi(Product $product): array
    {
        if ($product->productVariantValues->isEmpty()) {
            return [];
        }

        return $product->productVariantValues
            ->mapWithKeys(fn ($value) => [
                $value->attribute?->name => $value->value,
            ])
            ->toArray();
    }

    public function getAttributesWithNameAndValueKey(Product $product): array
    {
        $attributes = [];
        if (config('app.product_variant')) {
            foreach ($product->productVariantValues as $productVariantValue) {
                $attributes[] = [
                    'name' => $productVariantValue->attribute?->name,
                    'value' => $productVariantValue->value,
                ];
            }
        }

        return $attributes;
    }

    public function getJsonAttributeToString(string $productVariants): string
    {
        $productVariantData = json_decode($productVariants, true);

        if (is_array($productVariantData)) {
            return collect($productVariantData)
                ->map(
                    fn ($variant) => isset($variant['attribute_name'], $variant['attribute_value'])
                        ? $variant['attribute_name'] . ' : ' . $variant['attribute_value']
                        : null
                )
                ->filter()
                ->implode(', ');
        }

        return '';
    }

    public function getStandardActiveInventoryProductDetailsForArticleNumber(
        ProductArticleData $productArticleData,
        int $companyId
    ): array {
        $productQueries = resolve(ProductQueries::class);
        $inventoryQueries = resolve(InventoryQueries::class);

        $products = $productQueries->searchActiveInventoryProductsByArticleNumber(
            $productArticleData->article_number,
            $companyId
        );
        $sourceInventories = collect([]);
        $destinationInventories = collect([]);

        foreach ($products as $product) {
            $sourceInventory = $inventoryQueries->fetchOrCreate(
                (int) $productArticleData->source_location_id,
                $product->id
            );

            $sourceInventories->push($sourceInventory);

            $destinationInventory = $inventoryQueries->fetchOrCreate(
                (int) $productArticleData->destination_location_id,
                $product->id
            );

            $destinationInventories->push($destinationInventory);
        }

        $colors = $products->sortBy('color.name')->pluck('color.name')->unique()->filter()->toArray();
        $sizes = $products->sortBy('size.sort_order')->pluck('size.name')->unique()->filter()->toArray();

        $products = $products->map(function ($product) use ($sourceInventories, $destinationInventories): array {
            /** @var ?Size $size */
            $size = $product->size?->name;
            /** @var ?Color $color */
            $color = $product->color?->name;

            return [
                'id' => $product->id,
                'has_batch' => $product->has_batch,
                'compound_product_name' => $product->compound_product_name,
                'color' => $product->color,
                'size' => $product->size,
                'unit_of_measure' => $product->unitOfMeasure,
                'stock' => null,
                'combination' => $color . ' ' . $size,
                'name' => $product->name,
                'source_stock' => (float) $sourceInventories->where('product_id', $product->id)->first()?->stock,
                'destination_stock' => (float) $destinationInventories->where(
                    'product_id',
                    $product->id
                )->first()?->stock,
            ];
        });

        return [
            'products' => $products,
            'xNames' => $sizes,
            'yNames' => $colors,
        ];
    }

    private function getActiveInventoryVariantProductDetails(
        ProductArticleData $productArticleData,
        int $companyId
    ): array {
        $masterProductQueries = resolve(MasterProductQueries::class);
        $inventoryQueries = resolve(InventoryQueries::class);

        $masterProduct = $masterProductQueries->searchByArticleNumberWithNonInventory(
            $productArticleData->article_number,
            $companyId
        );
        if (! $masterProduct) {
            return $this->emptyProductResponse();
        }

        $sourceInventories = collect([]);
        $destinationInventories = collect([]);

        foreach ($masterProduct->productVariants as $product) {
            $sourceInventory = $inventoryQueries->fetchOrCreate(
                (int) $productArticleData->source_location_id,
                $product->id
            );

            $sourceInventories->push($sourceInventory);

            $destinationInventory = $inventoryQueries->fetchOrCreate(
                (int) $productArticleData->destination_location_id,
                $product->id
            );

            $destinationInventories->push($destinationInventory);
        }

        $products = $this->mapActiveInventoryProductVariants(
            $masterProduct,
            $sourceInventories,
            $destinationInventories
        );

        $attributeNames = $products->pluck('attribute_names')->unique()->filter()->toArray();
        $variantValues = $products->pluck('variant_values')->unique()->filter()->toArray();
        if (count($attributeNames[0]) < 2) {
            return $this->emptyProductResponse();
        }

        [$yNames, $xNames] = $this->splitVariantValues($variantValues, count($attributeNames[0]));
        $xFormatNames = $this->formatVariantNames($xNames);
        $yFormatNames = $this->formatVariantNames($yNames);

        return [
            'products' => $products,
            'attributeNames' => $attributeNames,
            'xNames' => $xFormatNames,
            'yNames' => $yFormatNames,
        ];
    }

    private function mapActiveInventoryProductVariants(MasterProduct $masterProduct, Collection $sourceInventories,
        Collection $destinationInventories): Collection
    {
        return $masterProduct->productVariants->map(function ($product) use ($masterProduct, $sourceInventories,
            $destinationInventories): array {
            $attributeNames = $product->productVariantValues->pluck('attribute.name')->toArray();
            $variantValues = $product->productVariantValues->pluck('value')->toArray();

            return [
                'id' => $product->id,
                'has_batch' => $masterProduct->has_batch,
                'compound_product_name' => $product->compound_product_name,
                'attribute_names' => $attributeNames,
                'variant_values' => $variantValues,
                'unit_of_measure' => $masterProduct->unitOfMeasure,
                'stock' => null,
                'combination' => implode(' ', $variantValues),
                'name' => $product->name,
                'source_stock' => (float) $sourceInventories->where('product_id', $product->id)->first()?->stock,
                'destination_stock' => (float) $destinationInventories->where(
                    'product_id',
                    $product->id
                )->first()?->stock,
                'product_variant_values' => config('app.product_variant') ? $product->productVariantValues ?? [] : [],
            ];
        });
    }

    private function getStandardProductArticleNumberWithLocationStock(
        ProductWithLocationStockData $productWithLocationStockData,
        int $companyId
    ): array {
        $productQueries = resolve(ProductQueries::class);
        $products = $productQueries->searchByArticleNumberWithDerivatives(
            $productWithLocationStockData->article_number,
            $companyId
        );

        $purchaseOrderService = resolve(PurchaseOrderService::class);
        $locationInventories = $purchaseOrderService->getLocationStock(
            $products->pluck('id')->toArray(),
            $productWithLocationStockData->location_id,
            $productWithLocationStockData->external_location_id,
        );

        $colors = $products->sortBy('color.name')->pluck('color.name')->unique()->filter()->toArray();
        $sizes = $products->sortBy('size.sort_order')->pluck('size.name')->unique()->filter()->toArray();

        $products = $products->map(function ($product) use ($locationInventories): array {
            /** @var ?Size $size */
            $size = $product->size?->name;
            /** @var ?Color $color */
            $color = $product->color?->name;
            /** @var ?UnitOfMeasure $unitOfMeasure */
            $unitOfMeasure = $product->unitOfMeasure;
            /** @var ?UnitOfMeasureDerivative $derivatives */
            $derivatives = $unitOfMeasure?->derivatives;
            $inventory = collect($locationInventories)->where('product_id', $product->id)->first();

            return [
                'id' => $product->id,
                'has_batch' => $product->has_batch,
                'color' => $product->color,
                'size' => $product->size,
                'unit_of_measure' => $unitOfMeasure,
                'derivatives' => $derivatives,
                'combination' => $color . ' ' . $size,
                'name' => $product->name,
                'compound_product_name' => $product->compound_product_name,
                'stock' => $inventory ? (int) $inventory['stock'] : 0,
                'reserved_stock' => $inventory ? (int) $inventory['reserved_stock'] : 0,
                'external_stock' => $inventory ? (int) $inventory['external_stock'] : 0,
                'external_reserved_stock' => $inventory ? (int) $inventory['external_reserved_stock'] : 0,
            ];
        });

        return [
            'products' => $products,
            'xNames' => $colors,
            'yNames' => $sizes,
        ];
    }

    private function getVariantProductArticleNumberWithLocationStock(
        ProductWithLocationStockData $productWithLocationStockData,
        int $companyId
    ): array {
        $masterProductQueries = resolve(MasterProductQueries::class);

        $masterProduct = $masterProductQueries->searchByArticleNumberWithNonInventory(
            $productWithLocationStockData->article_number,
            $companyId
        );

        if (! $masterProduct) {
            return $this->emptyProductResponse();
        }

        $purchaseOrderService = resolve(PurchaseOrderService::class);
        $locationInventories = $purchaseOrderService->getLocationStock(
            $masterProduct->productVariants->pluck('id')->toArray(),
            $productWithLocationStockData->location_id,
            $productWithLocationStockData->external_location_id,
        );

        $products = $this->mapVariantArticleNumberWithLocation($masterProduct, $locationInventories);

        $attributeNames = $products->pluck('attribute_names')->unique()->filter()->toArray();
        $variantValues = $products->pluck('variant_values')->unique()->filter()->toArray();
        if (count($attributeNames[0]) < 2) {
            return $this->emptyProductResponse();
        }

        [$xNames, $yNames] = $this->splitVariantValues($variantValues, count($attributeNames[0]));
        $xFormatNames = $this->formatVariantNames($xNames);
        $yFormatNames = $this->formatVariantNames($yNames);

        return [
            'products' => $products,
            'attributeNames' => $attributeNames,
            'xNames' => $xFormatNames,
            'yNames' => $yFormatNames,
        ];
    }

    private function mapVariantArticleNumberWithLocation(
        MasterProduct $masterProduct,
        array $locationInventories,
    ): Collection {
        return $masterProduct->productVariants->map(function ($product) use (
            $masterProduct,
            $locationInventories,
        ): array {
            /** @var ?UnitOfMeasure $unitOfMeasure */
            $unitOfMeasure = $masterProduct->unitOfMeasure;

            /** @var ?UnitOfMeasureDerivative $derivatives */
            $derivatives = $unitOfMeasure?->derivatives;
            $inventory = collect($locationInventories)->where('product_id', $product->id)->first();

            $attributeNames = $product->productVariantValues->pluck('attribute.name')->toArray();
            $variantValues = $product->productVariantValues->pluck('value')->toArray();

            return [
                'id' => $product->id,
                'has_batch' => $masterProduct->has_batch,
                'attribute_names' => $attributeNames,
                'variant_values' => $variantValues,
                'unit_of_measure' => $unitOfMeasure,
                'derivatives' => $derivatives,
                'combination' => implode(' ', $variantValues),
                'name' => $product->name,
                'compound_product_name' => $product->compound_product_name,
                'stock' => $inventory ? (int) $inventory['stock'] : 0,
                'reserved_stock' => $inventory ? (int) $inventory['reserved_stock'] : 0,
                'external_stock' => $inventory ? (int) $inventory['external_stock'] : 0,
                'external_reserved_stock' => $inventory ? (int) $inventory['external_reserved_stock'] : 0,
                'product_variant_values' => config('app.product_variant') ? $product->productVariantValues ?? [] : [],
            ];
        });
    }
}
