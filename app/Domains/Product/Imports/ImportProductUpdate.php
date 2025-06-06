<?php

declare(strict_types=1);

namespace App\Domains\Product\Imports;

use App\CommonFunctions;
use App\Domains\Brand\BrandQueries;
use App\Domains\Category\CategoryQueries;
use App\Domains\Color\ColorQueries;
use App\Domains\Department\DepartmentQueries;
use App\Domains\ImportRecord\Enums\ColumnValidationIssueTypes;
use App\Domains\ImportRecord\Interfaces\ImportRecordClassInterface;
use App\Domains\ImportRecord\Services\ImportRecordService;
use App\Domains\MasterProduct\Services\MasterProductService;
use App\Domains\Permission\Services\PermissionModuleService;
use App\Domains\Product\DataObjects\ProductData;
use App\Domains\Product\Enums\BulkProductUpdateImportColumns;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Product\Enums\Statuses;
use App\Domains\Product\ProductQueries;
use App\Domains\Product\SubDepartment\Enums\SubDepartments;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Domains\Season\SeasonQueries;
use App\Domains\Size\SizeQueries;
use App\Domains\Style\StyleQueries;
use App\Domains\Tag\TagQueries;
use App\Domains\UnitOfMeasure\UnitOfMeasureQueries;
use App\Domains\Vendor\VendorQueries;
use App\Models\ImportRecord;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ImportProductUpdate implements ImportRecordClassInterface
{
    public function validate(array $productDetails, ImportRecord $importRecord): array
    {
        // if you add/update any column(s), update the BulkProductUpdateImportColumns class.

        $validationErrors = [];

        $productQueries = resolve(ProductQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $unitOfMeasureQueries = resolve(UnitOfMeasureQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);
        $vendorQueries = resolve(VendorQueries::class);
        $saleChannelQueries = resolve(SaleChannelQueries::class);

        if (! array_key_exists('name', $productDetails) || ! $productDetails['name']) {
            $validationErrors[] = 'The name is required.';
        }

        if (array_key_exists('original_created_at', $productDetails) && $productDetails['original_created_at']) {
            try {
                /** @var Carbon $date */
                $date = Carbon::createFromFormat('Y-m-d H:i:s', $productDetails['original_created_at']);
                if ($date->format('Y-m-d H:i:s') !== $productDetails['original_created_at']) {
                    $validationErrors[] = 'The original created at field must be a valid date in the format Y-m-d H:i:s.';
                }
            } catch (Throwable) {
                $validationErrors[] = 'The original created at field must be a valid date in the format Y-m-d H:i:s.';
            }
        }

        if (
            array_key_exists('status', $productDetails) && $productDetails['status'] &&
            Str::lower($productDetails['status']) === Str::lower(Statuses::DRAFT->name)
        ) {
            $validationErrors[] = 'The status must be active/archived.';
        }

        if (! array_key_exists('category_name', $productDetails) || ! $productDetails['category_name']) {
            $validationErrors[] = 'The category name is mandatory.';
        }

        if (
            array_key_exists('sub_department', $productDetails)
            && $productDetails['sub_department']
            && ! SubDepartments::getValueByCaseName($productDetails['sub_department'])
        ) {
            $validationErrors[] = 'The specified subdepartment is not available in our records.';
        }

        if (! array_key_exists('brand', $productDetails) || ! $productDetails['brand']) {
            $validationErrors[] = 'The brand is mandatory.';
        } elseif (! $brandQueries->existsByName((string) $productDetails['brand'], $importRecord->company_id)) {
            $validationErrors[] = 'The specified brand is not available in our records..';
        }

        if (config('app.update_unit_of_measure') && (array_key_exists('unit_of_measure', $productDetails)
        && $productDetails['unit_of_measure']
        && ! $unitOfMeasureQueries->existsByName(
            (string) $productDetails['unit_of_measure'],
            $importRecord->company_id
        ))) {
            $validationErrors[] = 'The specified Unit Of Measure is not available in our records..';
        }

        if (
            ! array_key_exists('has_batch', $productDetails) || ! $productDetails['has_batch']
        ) {
            $validationErrors[] = 'The batch value is required.';
        }

        if (
            ! array_key_exists('type_id', $productDetails) || ! $productDetails['type_id']
        ) {
            $validationErrors[] = 'The value of the type ID is required.';
        }

        if (
            array_key_exists('type_id', $productDetails)
            && $productDetails['type_id']
            && ! ProductTypes::getValueByCaseName($productDetails['type_id'])
        ) {
            $validationErrors[] = 'The specified type ID is not available in our records..';
        }

        if (
            array_key_exists('type_id', $productDetails) &&
            CommonFunctions::stringTitleLowerCase(ProductTypes::REGULAR_PRODUCT->name) !== $productDetails['type_id'] &&
            array_key_exists('minimum_price', $productDetails) &&
            ! $productDetails['minimum_price']
        ) {
            $validationErrors[] = 'The minimum price is required when a non-regular product type is selected.';
        }

        if (
            ! array_key_exists('is_non_inventory', $productDetails) || ! $productDetails['is_non_inventory']
        ) {
            $validationErrors[] = 'The non-inventory value is required.';
        }

        if (! array_key_exists('upc', $productDetails) || ! $productDetails['upc']) {
            $validationErrors[] = 'A UPC is required.';

            return $validationErrors;
        }

        $product = $productQueries->getByUpcAndCompanyId((string) $productDetails['upc'], $importRecord->company_id);
        if (! $product) {
            $validationErrors[] = 'The specified UPC is not available in our records.';

            return $validationErrors;
        }

        if (
            array_key_exists('verification_qr_code', $productDetails)
            && null !== $productDetails['verification_qr_code']
            && '' !== $productDetails['verification_qr_code']
        ) {
            $isQrCodeExists = $productQueries->existsByQrCode(
                (string) $productDetails['verification_qr_code'],
                $importRecord->company_id,
                (string) $productDetails['upc']
            );
            if ($isQrCodeExists) {
                $validationErrors[] = 'The specified Verification Qr Code is already available in our records.';
            }
        }

        if (array_key_exists('code', $productDetails) && $productDetails['code']) {
            $product = $productQueries->existsByCodeUsingUpc(
                (string) $productDetails['code'],
                $importRecord->company_id,
                (string) $productDetails['upc']
            );

            if ($product) {
                $validationErrors[] = 'Specified code is already taken.';
            }
        }

        if (
            array_key_exists('category_name', $productDetails)
            && $productDetails['category_name']
            && ! $categoryQueries->existsByName($productDetails['category_name'], $importRecord->company_id)
        ) {
            $validationErrors[] = 'Specified Category is not available in our records.';
        }

        if (
            array_key_exists('subcategory_name', $productDetails)
            && $productDetails['subcategory_name']
            && ! $categoryQueries->existsByName($productDetails['subcategory_name'], $importRecord->company_id)
        ) {
            $validationErrors[] = 'Specified Subcategory is not available in our records.';
        }

        if (
            array_key_exists('subsubcategory_name', $productDetails)
            && $productDetails['subsubcategory_name']
            && ! $categoryQueries->existsByName($productDetails['subsubcategory_name'], $importRecord->company_id)
        ) {
            $validationErrors[] = 'Specified Sub Subcategory is not available in our records.';
        }

        if (
            array_key_exists('vendor', $productDetails)
            && $productDetails['vendor']
        ) {
            $vendor = $vendorQueries->existsByName($productDetails['vendor'], $importRecord->company_id);
            if (! $vendor) {
                $validationErrors[] = 'Specified vendor is not available in our records.';
            }
        }

        if (
            array_key_exists('is_available_in_ecommerce', $productDetails)
            && 'Yes' === $productDetails['is_available_in_ecommerce']
            && array_key_exists('sale_channels', $productDetails)
            && ! $productDetails['sale_channels']
        ) {
            $validationErrors[] = 'The sale channel is mandatory. When is_available_in_ecommerce value is Yes.';
        }

        if (
            array_key_exists('sale_channels', $productDetails) &&
            $productDetails['sale_channels'] &&
            ! $saleChannelQueries->doSaleChannelNamesExists(
                array_map('trim', explode(',', $productDetails['sale_channels'])),
                $importRecord->company_id
            )
        ) {
            $validationErrors[] = 'The specified sale channels is not available in our records.';
        }

        return $validationErrors;
    }

    public function save(array $productDetails, ImportRecord $importRecord): void
    {
        $productQueries = resolve(ProductQueries::class);
        $seasonQueries = resolve(SeasonQueries::class);
        $departmentQueries = resolve(DepartmentQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $styleQueries = resolve(StyleQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $tagQueries = resolve(TagQueries::class);
        $unitOfMeasureQueries = resolve(UnitOfMeasureQueries::class);
        $vendorQueries = resolve(VendorQueries::class);
        $saleChannelQueries = resolve(SaleChannelQueries::class);
        $masterProductService = resolve(MasterProductService::class);

        $tagIds = [];

        if (array_key_exists('tags', $productDetails) && null !== $productDetails['tags']) {
            $productTags = explode(',', $productDetails['tags']);
            $tags = $tagQueries->existsByNames(array_map('trim', $productTags), $importRecord->company_id);
            $tagIds = $tags->toArray();
        }

        $seasonId = $productDetails['season'] ? $seasonQueries->getIdByName(
            (string) $productDetails['season'],
            $importRecord->company_id
        ) : null;

        $vendorId = null;
        if (array_key_exists('vendor', $productDetails) && null !== $productDetails['vendor']) {
            $vendor = $vendorQueries->getIdByName((string) $productDetails['vendor'], $importRecord->company_id);

            $vendorId = $vendor ? $vendor->id : null;
        }

        $departmentId = $productDetails['department'] ? $departmentQueries->getIdByName(
            (string) $productDetails['department'],
            $importRecord->company_id
        ) : null;

        $colorId = $productDetails['color'] ? $colorQueries->getIdByName(
            (string) $productDetails['color'],
            $importRecord->company_id
        ) : null;

        $sizeId = $productDetails['size'] ? $sizeQueries->getIdByName(
            (string) $productDetails['size'],
            $importRecord->company_id
        ) : null;

        $styleId = $productDetails['style'] ? $styleQueries->getIdByName(
            (string) $productDetails['style'],
            $importRecord->company_id
        ) : null;

        $brandId = $brandQueries->getIdByName($productDetails['brand']);

        $categoryId = $productDetails['category_name'] ? $categoryQueries->getIdByName(
            (string) $productDetails['category_name'],
            $importRecord->company_id
        ) : null;

        $subCategoryId = $productDetails['subcategory_name'] ? $categoryQueries->getIdByName(
            (string) $productDetails['subcategory_name'],
            $importRecord->company_id,
        ) : null;

        $subSubCategoryId = $productDetails['subsubcategory_name'] ? $categoryQueries->getIdByName(
            (string) $productDetails['subsubcategory_name'],
            $importRecord->company_id,
        ) : null;

        $isNonSellingItem = array_key_exists(
            'is_non_selling_item',
            $productDetails
        ) ? $productDetails['is_non_selling_item'] : 'No';

        $unitOfMeasureId = null;
        if (config('app.update_unit_of_measure')) {
            $unitOfMeasureId = array_key_exists(
                'unit_of_measure',
                $productDetails
            ) && null !== $productDetails['unit_of_measure'] ? $unitOfMeasureQueries->getIdByName(
                (string) $productDetails['unit_of_measure'],
                $importRecord->company_id
            ) : null;
        }

        $saleChannelIds = [];
        if (array_key_exists(
            'sale_channels',
            $productDetails
        ) && 'Yes' === $productDetails['is_available_in_ecommerce'] && null !== $productDetails['sale_channels']) {
            $productSaleChannels = explode(',', $productDetails['sale_channels']);
            $saleChannelIds = $saleChannelQueries->existsByNames(
                array_map('trim', $productSaleChannels),
                $importRecord->company_id
            );
        }

        $isAvailableInEcommerce = array_key_exists(
            'is_available_in_ecommerce',
            $productDetails
        ) ? $productDetails['is_available_in_ecommerce'] : 'No';

        $isSoldAsSingleItem = array_key_exists(
            'is_sold_as_single_item',
            $productDetails
        ) ? $productDetails['is_sold_as_single_item'] : 'No';

        $sellItemViaDerivative = array_key_exists(
            'sell_item_via_derivative',
            $productDetails
        ) ? $productDetails['sell_item_via_derivative'] : 'Yes';

        $isAvailableInPos = array_key_exists(
            'is_available_in_pos',
            $productDetails
        ) ? $productDetails['is_available_in_pos'] : 'Yes';

        if ('No' !== $isNonSellingItem) {
            $isAvailableInEcommerce = 'No';
            $isAvailableInPos = 'No';
        }

        $productData = [
            'name' => (string) $productDetails['name'],
            'description' => array_key_exists('description', $productDetails) ?
                (string) $productDetails['description'] :
                null,
            'vendor_id' => $vendorId,
            'code' => $productDetails['code'] ? (string) $productDetails['code'] : null,
            'season_id' => $seasonId,
            'department_id' => $departmentId,
            'sub_department_id' => $productDetails['sub_department'] ? SubDepartments::getValueByCaseName(
                $productDetails['sub_department']
            ) : null,
            'color_id' => $colorId,
            'size_id' => $sizeId,
            'brand_id' => $brandId,
            'style_id' => $styleId,
            'verification_qr_code' => $productDetails['verification_qr_code'],
            'ean' => (string) $productDetails['ean'],
            'manufacturer_sku' => (string) $productDetails['manufacturer_sku'],
            'custom_sku' => (string) $productDetails['custom_sku'],
            'article_number' => (string) $productDetails['article_number'],
            'retail_price' => array_key_exists(
                'retail_price',
                $productDetails
            ) ? (float) $productDetails['retail_price'] : null,
            'franchise_price_1' => array_key_exists(
                'franchise_price_1',
                $productDetails
            ) ? (float) $productDetails['franchise_price_1'] : null,
            'franchise_price_2' => array_key_exists(
                'franchise_price_2',
                $productDetails
            ) ? (float) $productDetails['franchise_price_2'] : null,
            'franchise_price_3' => array_key_exists(
                'franchise_price_3',
                $productDetails
            ) ? (float) $productDetails['franchise_price_3'] : null,
            'wholesale_price' => array_key_exists(
                'wholesale_price',
                $productDetails
            ) ? (float) $productDetails['wholesale_price'] : null,
            'company_or_tender_price' => array_key_exists(
                'company_or_tender_price',
                $productDetails
            ) ? (float) $productDetails['company_or_tender_price'] : null,
            'branch_price' => array_key_exists(
                'branch_price',
                $productDetails
            ) ? (float) $productDetails['branch_price'] : null,
            'minimum_price' => array_key_exists(
                'minimum_price',
                $productDetails
            ) ? (float) $productDetails['minimum_price'] : null,
            'original_capital_price' => array_key_exists(
                'original_capital_price',
                $productDetails
            ) ? (float) $productDetails['original_capital_price'] : null,
            'capital_price' => array_key_exists(
                'capital_price',
                $productDetails
            ) ? (float) $productDetails['capital_price'] : null,
            'purchase_cost' => array_key_exists(
                'purchase_cost',
                $productDetails
            ) ? (float) $productDetails['purchase_cost'] : null,
            'staff_price' => array_key_exists(
                'staff_price',
                $productDetails
            ) ? (float) $productDetails['staff_price'] : 0,
            'online_price' => array_key_exists(
                'online_price',
                $productDetails
            ) ? (float) $productDetails['online_price'] : null,
            'is_temporarily_unavailable' => 'Yes' === $productDetails['is_temporarily_unavailable'],
            'type_id' => (string) ProductTypes::getValueByCaseName($productDetails['type_id']),
            'has_batch' => 'Yes' === $productDetails['has_batch'],
            'category_ids' => [$categoryId, $subCategoryId, $subSubCategoryId],
            'tag_ids' => $tagIds,
            'status' => $this->getStatusIdUsingName((string) $productDetails['status']),
            'is_non_inventory' => 'Yes' === $productDetails['is_non_inventory'],
            'is_non_selling_item' => 'Yes' === $isNonSellingItem,
            'is_available_in_pos' => 'Yes' === $isAvailableInPos,
            'is_available_in_ecommerce' => 'Yes' === $isAvailableInEcommerce,
            'sell_item_via_derivative' => 'Yes' === $sellItemViaDerivative,
            'is_sold_as_single_item' => 'Yes' === $isSoldAsSingleItem,
            'original_created_at' => $productDetails['original_created_at'] ?? null,
            'sale_channel_ids' => $saleChannelIds,
        ];

        if (config('app.update_unit_of_measure')) {
            $productData['unit_of_measure_id'] = $unitOfMeasureId;
        }

        DB::beginTransaction();
        try {
            $product = $productQueries->updateByUpc(
                $productData,
                (string) $productDetails['upc'],
                (int) $importRecord->company_id
            );

            if (null !== $product->master_product_id) {
                unset($productData['status']);
                $productData['upc'] = $product->upc;
                $productData['thumbnail'] = null;
                $productData['images'] = [];
                $productData['videos'] = [];
                $productData['is_warranty'] = (bool) $product->is_warranty;
                $productData['unit_of_measure_id'] = $unitOfMeasureId;
                $newProductData = new ProductData(...$productData);
                $masterProductService->createOrUpdateFromProduct($product, $newProductData);
            }

            DB::commit();
        } catch (Throwable $throwable) {
            Log::error('Import-Product', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
            DB::rollBack();
        }
    }

    public function validateColumns(array $uploadHeaderColumns, array $allowedPermissionLists, int $companyId): array
    {
        $requiredPermissions = array_map(
            fn ($value): string => 'product_' . $value,
            PermissionModuleService::getModuleSubLists()['Product']
        );

        $optionalColumns = array_map(
            fn ($value): string => Str::replace('product_', '', $value),
            array_diff($requiredPermissions, $allowedPermissionLists)
        );

        $importRecordService = resolve(ImportRecordService::class);

        if ([] !== $optionalColumns) {
            $requiredHeaderColumns = collect(BulkProductUpdateImportColumns::cases())
                ->whereNotIn('value', $optionalColumns)
                ->pluck('value')
                ->toArray();

            $invalidColumns = array_intersect($optionalColumns, array_keys($uploadHeaderColumns));

            if ([] !== $invalidColumns) {
                return [
                    'type' => ColumnValidationIssueTypes::PERMISSION_ISSUE->value,
                    'status' => $importRecordService->validateColumn($requiredHeaderColumns, $uploadHeaderColumns),
                    'columns' => $optionalColumns,
                ];
            }

            return [
                'type' => ColumnValidationIssueTypes::COLUMN_ISSUE->value,
                'status' => $importRecordService->validateColumn($requiredHeaderColumns, $uploadHeaderColumns),
            ];
        }

        $requiredHeaderColumns = collect(BulkProductUpdateImportColumns::cases())->pluck('value')->toArray();

        return [
            'type' => ColumnValidationIssueTypes::COLUMN_ISSUE->value,
            'status' => $importRecordService->validateColumn($requiredHeaderColumns, $uploadHeaderColumns),
        ];
    }

    private function getStatusIdUsingName(string $statusName): int
    {
        if (Str::lower(Statuses::ACTIVE->name) === Str::lower($statusName)) {
            return Statuses::ACTIVE->value;
        }

        if (Str::lower(Statuses::ARCHIVED->name) === Str::lower(trim($statusName))) {
            return Statuses::ARCHIVED->value;
        }

        return Statuses::ACTIVE->value;
    }
}
