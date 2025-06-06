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
use App\Domains\Product\Enums\ProductImportColumns;
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
use Illuminate\Support\Str;
use Throwable;

class ImportProduct implements ImportRecordClassInterface
{
    public function validate(array $productDetails, ImportRecord $importRecord): array
    {
        $validationErrors = [];

        $productQueries = resolve(ProductQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);
        $vendorQueries = resolve(VendorQueries::class);
        $saleChannelQueries = resolve(SaleChannelQueries::class);
        $departmentQueries = resolve(DepartmentQueries::class);

        if (! array_key_exists('name', $productDetails) || ! $productDetails['name']) {
            $validationErrors[] = 'The name is required.';
        }

        try {
            if ($productDetails['original_created_at']) {
                /** @var Carbon $date */
                $date = Carbon::createFromFormat('Y-m-d H:i:s', $productDetails['original_created_at']);
                if ($date->format('Y-m-d H:i:s') !== $productDetails['original_created_at']) {
                    $validationErrors[] = 'The original created at field must be a valid date in the format Y-m-d H:i:s.';
                }
            }
        } catch (Throwable) {
            $validationErrors[] = 'The date format is not valid please enter valid date format Y-m-d H:i:s (2024-12-31 23:59:59).';
        }

        if (
            array_key_exists('code', $productDetails)
            && $productDetails['code']
        ) {
            $product = $productQueries->getByCodeAndCompanyId(
                (string) $productDetails['code'],
                $importRecord->company_id
            );

            if ($product) {
                $validationErrors[] = 'The specified code is already available in our records.';
            }

            if ($product && Statuses::ARCHIVED->value === $product->status) {
                $validationErrors[] = 'The specified UPC has already been archived.';
            }
        }

        if (array_key_exists('upc', $productDetails) && $productDetails['upc']) {
            $product = $productQueries->getByUpcAndCompanyId(
                (string) $productDetails['upc'],
                $importRecord->company_id
            );

            if ($product) {
                $validationErrors[] = 'The specified UPC is already available in our records.';
            }

            if ($product && Statuses::ARCHIVED->value === $product->status) {
                $validationErrors[] = 'The specified UPC has already been archived.';
            }
        }

        if (
            array_key_exists('verification_qr_code', $productDetails)
            && null !== $productDetails['verification_qr_code']
            && '' !== $productDetails['verification_qr_code']
        ) {
            $product = $productQueries->existsByQrCode(
                (string) $productDetails['verification_qr_code'],
                $importRecord->company_id,
                (string) $productDetails['upc']
            );

            if ($product) {
                $validationErrors[] = 'The specified Verification Qr Code is already available in our records.';
            }
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
            $validationErrors[] = 'The specified brand is not available in our records.';
        }

        if (
            array_key_exists('department', $productDetails) &&
            $productDetails['department'] &&
            ! $departmentQueries->existsByName((string) $productDetails['department'], $importRecord->company_id)
        ) {
            $validationErrors[] = 'The specified department is not available in our records.';
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
            $validationErrors[] = 'The specified type ID is not available in our records.';
        }

        if (
            array_key_exists('type_id', $productDetails)
            && ProductTypes::getFormattedCaseName(ProductTypes::ASSEMBLY_PRODUCT->value) === $productDetails['type_id']
        ) {
            $validationErrors[] = 'The specified type assembly product cannot added..';
        }

        if (
            (
                array_key_exists('type_id', $productDetails) &&
                CommonFunctions::stringTitleLowerCase(
                    ProductTypes::REGULAR_PRODUCT->name
                ) !== $productDetails['type_id']
            ) &&
            array_key_exists('minimum_price', $productDetails) && ! $productDetails['minimum_price']
        ) {
            $validationErrors[] = 'The minimum price is required when a non-regular product type is selected.';
        }

        if (
            ! array_key_exists('is_non_inventory', $productDetails) || ! $productDetails['is_non_inventory']
        ) {
            $validationErrors[] = 'The non-inventory value is required.';
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
            $productDetails['sale_channels'] &&
            ! $saleChannelQueries->doSaleChannelNamesExists(
                array_map('trim', explode(',', $productDetails['sale_channels'])),
                $importRecord->company_id
            )
        ) {
            $validationErrors[] = 'The specified sale channels is not available in our records.';
        }

        if (! config('services.import_product_record.create_with_category')) {
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

        return $validationErrors;
    }

    public function save(array $productDetails, ImportRecord $importRecord): void
    {
        $productQueries = resolve(ProductQueries::class);
        $unitOfMeasureQueries = resolve(UnitOfMeasureQueries::class);
        $seasonQueries = resolve(SeasonQueries::class);
        $departmentQueries = resolve(DepartmentQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $styleQueries = resolve(StyleQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $tagQueries = resolve(TagQueries::class);
        $vendorQueries = resolve(VendorQueries::class);
        $saleChannelQueries = resolve(SaleChannelQueries::class);
        $masterProductService = resolve(MasterProductService::class);

        $unitOfMeasureId = array_key_exists(
            'unit_of_measure',
            $productDetails
        ) && null !== $productDetails['unit_of_measure'] ? $unitOfMeasureQueries->getIdByName(
            (string) $productDetails['unit_of_measure'],
            $importRecord->company_id
        ) : null;

        $seasonId = array_key_exists(
            'season',
            $productDetails
        ) && null !== $productDetails['season'] ? $seasonQueries->getIdByName(
            (string) $productDetails['season'],
            $importRecord->company_id
        ) : null;

        $vendorId = null;
        if (array_key_exists('vendor', $productDetails) && null !== $productDetails['vendor']) {
            $vendor = $vendorQueries->getIdByName((string) $productDetails['vendor'], $importRecord->company_id);
            $vendorId = $vendor ? $vendor->id : null;
        }

        $departmentId = null;
        if (array_key_exists('department', $productDetails) && null !== $productDetails['department']) {
            $departmentId = $departmentQueries->getIdByNameForImportRecord((string) $productDetails['department']);
        }

        $colorId = array_key_exists(
            'color',
            $productDetails
        ) && null !== $productDetails['color'] ? $colorQueries->getIdByName(
            (string) $productDetails['color'],
            $importRecord->company_id
        ) : null;

        $sizeId = array_key_exists(
            'size',
            $productDetails
        ) && null !== $productDetails['size'] ? $sizeQueries->getIdByName(
            (string) $productDetails['size'],
            $importRecord->company_id
        ) : null;

        $styleId = array_key_exists(
            'style',
            $productDetails
        ) && null !== $productDetails['style'] ? $styleQueries->getIdByName(
            (string) $productDetails['style'],
            $importRecord->company_id
        ) : null;

        $brandId = $brandQueries->getIdByName((string) $productDetails['brand']);

        $categoryId = null;
        $subCategoryId = null;
        $subSubCategoryId = null;

        if (config('services.import_product_record.create_with_category')) {
            $categoryId = $productDetails['category_name'] ? $categoryQueries->createOrGetByName(
                (string) $productDetails['category_name'],
                $importRecord->company_id
            ) : null;

            if ($categoryId) {
                $subCategoryId = $productDetails['subcategory_name'] ? $categoryQueries->createSubCategoryOrGetByName(
                    (string) $productDetails['subcategory_name'],
                    $categoryId,
                    $importRecord->company_id,
                ) : null;

                if ($subCategoryId) {
                    $subSubCategoryId = $productDetails['subsubcategory_name'] ? $categoryQueries->createSubsubCategoryOrGetByName(
                        (string) $productDetails['subsubcategory_name'],
                        $subCategoryId,
                        $importRecord->company_id,
                    ) : null;
                }
            }
        } else {
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
        }

        $upc = $productDetails['upc'] ?? $this->generateUniqueUPCNumber($importRecord->company_id);

        $tagIds = [];

        if (array_key_exists('tags', $productDetails) && null !== $productDetails['tags']) {
            $productTags = explode(',', $productDetails['tags']);
            $tags = $tagQueries->existsByNames(array_map('trim', $productTags), $importRecord->company_id);
            $tagIds = $tags->toArray();
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

        $isNonSellingItem = $this->getValueFromColumnIfExists($productDetails, 'is_non_selling_item', 'No');

        $isAvailableInEcommerce = array_key_exists(
            'is_available_in_ecommerce',
            $productDetails
        ) ? $productDetails['is_available_in_ecommerce'] : 'No';

        $isSoldAsSingleItem = array_key_exists(
            'is_sold_as_single_item',
            $productDetails
        ) ? $productDetails['is_sold_as_single_item'] : 'No';

        $isAvailableInPos = array_key_exists(
            'is_available_in_pos',
            $productDetails
        ) ? $productDetails['is_available_in_pos'] : 'Yes';

        $sellItemViaDerivative = array_key_exists(
            'sell_item_via_derivative',
            $productDetails
        ) ? $productDetails['sell_item_via_derivative'] : 'Yes';

        if ('No' !== $isNonSellingItem) {
            $isAvailableInEcommerce = 'No';
            $isAvailableInPos = 'No';
        }

        $newProductData = new ProductData(
            name: (string) $productDetails['name'],
            description: (string) $this->getValueFromColumnIfExists($productDetails, 'description'),
            code: array_key_exists(
                'code',
                $productDetails
            ) && null !== $productDetails['code'] ? (string) $productDetails['code'] : null,
            unit_of_measure_id: $unitOfMeasureId,
            season_id: $seasonId,
            vendor_id: $vendorId,
            department_id: $departmentId,
            sub_department_id: array_key_exists(
                'sub_department',
                $productDetails
            ) && null !== $productDetails['sub_department'] ? SubDepartments::getValueByCaseName(
                $productDetails['sub_department']
            ) : null,
            color_id: $colorId,
            size_id: $sizeId,
            brand_id: $brandId,
            style_id: $styleId,
            upc: (string) $upc,
            verification_qr_code: (string) $productDetails['verification_qr_code'],
            ean: (string) $this->getValueFromColumnIfExists($productDetails, 'ean'),
            manufacturer_sku: (string) $this->getValueFromColumnIfExists($productDetails, 'manufacturer_sku'),
            custom_sku: (string) $this->getValueFromColumnIfExists($productDetails, 'custom_sku'),
            article_number: (string) $this->getValueFromColumnIfExists($productDetails, 'article_number'),
            retail_price: (float) $this->getValueFromColumnIfExists($productDetails, 'retail_price'),
            franchise_price_1: (float) $this->getValueFromColumnIfExists($productDetails, 'franchise_price_1'),
            franchise_price_2: (float) $this->getValueFromColumnIfExists($productDetails, 'franchise_price_2'),
            franchise_price_3: (float) $this->getValueFromColumnIfExists($productDetails, 'franchise_price_3'),
            wholesale_price: (float) $this->getValueFromColumnIfExists($productDetails, 'wholesale_price'),
            company_or_tender_price: (float) $this->getValueFromColumnIfExists(
                $productDetails,
                'company_or_tender_price'
            ),
            branch_price: (float) $this->getValueFromColumnIfExists($productDetails, 'branch_price'),
            minimum_price: (float) $this->getValueFromColumnIfExists($productDetails, 'minimum_price'),
            original_capital_price: (float) $this->getValueFromColumnIfExists(
                $productDetails,
                'original_capital_price'
            ),
            capital_price: (float) $this->getValueFromColumnIfExists($productDetails, 'capital_price'),
            staff_price: (float) $this->getValueFromColumnIfExists($productDetails, 'staff_price', 0),
            purchase_cost: (float) $this->getValueFromColumnIfExists($productDetails, 'purchase_cost'),
            online_price: (float) $this->getValueFromColumnIfExists($productDetails, 'online_price'),
            is_temporarily_unavailable: 'Yes' === $this->getValueFromColumnIfExists(
                $productDetails,
                'is_temporarily_unavailable',
                'No'
            ),
            type_id: (string) ProductTypes::getValueByCaseName($productDetails['type_id']),
            has_batch: 'Yes' === $this->getValueFromColumnIfExists($productDetails, 'has_batch', 'No'),
            category_ids: [$categoryId, $subCategoryId, $subSubCategoryId],
            thumbnail: null,
            is_non_inventory: 'Yes' === $this->getValueFromColumnIfExists($productDetails, 'is_non_inventory', 'No'),
            is_non_selling_item: 'Yes' === $isNonSellingItem,
            is_available_in_pos: 'Yes' === $isAvailableInPos,
            is_available_in_ecommerce: 'Yes' === $isAvailableInEcommerce,
            sell_item_via_derivative: 'Yes' === $sellItemViaDerivative,
            is_sold_as_single_item: 'Yes' === $isSoldAsSingleItem,
            tag_ids: $tagIds,
            original_created_at: $productDetails['original_created_at'] ?? null,
            retail_planning_hierarchy_id: null,
            sale_channel_ids: $saleChannelIds,
            is_warranty: false,
        );

        $product = $productQueries->addNew($newProductData, $importRecord->company_id, $importRecord->createdBy);
        $masterProductService->createOrUpdateFromProduct($product, $newProductData);
    }

    public function validateColumns(array $uploadHeaderColumns, array $allowedPermissionLists, int $companyId): array
    {
        $requiredPermissions = array_map(
            fn ($value): string => 'product_' . $value,
            PermissionModuleService::getModuleSubLists()['Product']
        );

        $notRequiredColumns = array_map(
            fn ($value): string => Str::replace('product_', '', $value),
            array_diff($requiredPermissions, $allowedPermissionLists)
        );

        $importRecordService = resolve(ImportRecordService::class);

        if ([] !== $notRequiredColumns) {
            $requiredHeaderColumns = collect(ProductImportColumns::cases())
                ->whereNotIn('value', $notRequiredColumns)
                ->pluck('value')
                ->toArray();

            $invalidColumns = array_intersect($notRequiredColumns, array_keys($uploadHeaderColumns));

            if ([] !== $invalidColumns) {
                return [
                    'type' => ColumnValidationIssueTypes::PERMISSION_ISSUE->value,
                    'status' => $importRecordService->validateColumn($requiredHeaderColumns, $uploadHeaderColumns),
                    'columns' => $notRequiredColumns,
                ];
            }

            return [
                'type' => ColumnValidationIssueTypes::COLUMN_ISSUE->value,
                'status' => $importRecordService->validateColumn($requiredHeaderColumns, $uploadHeaderColumns),
            ];
        }

        $requiredHeaderColumns = collect(ProductImportColumns::cases())->pluck('value')->toArray();

        return [
            'type' => ColumnValidationIssueTypes::COLUMN_ISSUE->value,
            'status' => $importRecordService->validateColumn($requiredHeaderColumns, $uploadHeaderColumns),
        ];
    }

    public function generateUniqueUPCNumber(int $companyId): string
    {
        $productQueries = resolve(ProductQueries::class);
        $upcNumber = CommonFunctions::getTwelveDigitNumber();

        $existUpcNumbers = $productQueries->existsByUpc($upcNumber, $companyId);

        if ($existUpcNumbers) {
            return $this->generateUniqueUPCNumber($companyId);
        }

        return $upcNumber;
    }

    private function getValueFromColumnIfExists(array $data, string $column, mixed $returnIfNoData = null): mixed
    {
        return array_key_exists($column, $data) ? $data[$column] : $returnIfNoData;
    }
}
