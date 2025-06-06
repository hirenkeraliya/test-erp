<?php

declare(strict_types=1);

namespace App\Domains\Common\Services;

use App\Domains\Brand\BrandQueries;
use App\Domains\Category\CategoryQueries;
use App\Domains\Color\ColorQueries;
use App\Domains\Common\Enums\ModelMappingTypes;
use App\Domains\Counter\CounterQueries;
use App\Domains\Department\DepartmentQueries;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Member\MemberQueries;
use App\Domains\Order\Enums\OrderTypes;
use App\Domains\Product\ProductQueries;
use App\Domains\ProductAgeingReport\Enums\AgeCategories;
use App\Domains\ProductAgeingReport\Enums\AgeOfProductTypes;
use App\Domains\ProductCollection\ProductCollectionQueries;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\PromoterGroup\PromoterGroupQueries;
use App\Domains\QuantitySold\Enums\ReportTypes;
use App\Domains\Region\RegionQueries;
use App\Domains\SaleThroughRatio\SaleThroughRatioQueries;
use App\Domains\Size\SizeQueries;
use App\Domains\Style\StyleQueries;
use App\Domains\Tag\TagQueries;

class PrintPdfHeaderFilterService
{
    protected array $filters = [
        'location_ids' => 'location_name',
        'location_id' => 'location_name',
        'compare_location_id' => 'compare_location_name',
        'product_id' => 'product_name',
        'product_collection_id' => 'product_collection',
        'category_ids' => 'category_name',
        'brand_ids' => 'brand',
        'promoter_ids' => 'promoter_name',
        'group_ids' => 'promoter_group_name',
        'size_ids' => 'size',
        'color_ids' => 'color',
        'department_ids' => 'department',
        'article_numbers' => 'article_number',
        'tag_ids' => 'tags',
        'age_of_product_type' => 'age_of_product_type',
        'age_category_id' => 'age_category',
        'last_selling_date_range' => 'last_selling_date_range',
        'counter_ids' => 'counters',
        'region_ids' => 'regions',
        'region_id' => 'regions',
        'compare_region_id' => 'compare_regions',
        'article_number' => 'article_number',
        'date_range' => 'date_range',
        'employee_id' => 'employee_name',
        'module_type' => 'module_name',
        'date' => 'date',
        'style_ids' => 'style',
        'grade_filter' => 'grade_filters',
        'report_type' => 'report_type',
        'location_type' => 'location_type',
        'compare_location_type' => 'compare_location_type',
        'type_id' => 'order_type',
        'member_id' => 'member_name',
        'e_invoice_submitted' => 'e_invoice_submitted',
    ];

    public function __construct(
        protected LocationQueries $locationQueries,
        protected ProductQueries $productQueries,
        protected ProductCollectionQueries $productCollectionQueries,
        protected CategoryQueries $categoryQueries,
        protected BrandQueries $brandQueries,
        protected PromoterQueries $promoterQueries,
        protected PromoterGroupQueries $promoterGroupQueries,
        protected SizeQueries $sizeQueries,
        protected ColorQueries $colorQueries,
        protected DepartmentQueries $departmentQueries,
        protected TagQueries $tagQueries,
        protected CounterQueries $counterQueries,
        protected RegionQueries $regionQueries,
        protected EmployeeQueries $employeeQueries,
        protected SaleThroughRatioQueries $saleThroughRatioQueries,
        protected StyleQueries $styleQueries,
        protected MemberQueries $memberQueries,
    ) {
    }

    /**
     * Dynamically build the filter header data array.
     */
    public function buildFilterData(array $filterData): array
    {
        $filterHeaderData = [];

        foreach ($this->filters as $key => $alias) {
            $value = $this->getFilterValue($filterData, $key);

            if (null !== $value) {
                $filterHeaderData[$alias] = $value;
            }
        }

        return $filterHeaderData;
    }

    /**
     * Retrieve the value based on the filter key, dynamically handling variations.
     */
    protected function getFilterValue(array $filterData, string $key): ?string
    {
        $keyVariants = [$key, rtrim($key, 's')];

        foreach ($keyVariants as $variant) {
            if (isset($filterData[$variant])) {
                return $this->callQueryMethod($variant, $filterData[$variant]);
            }
        }

        return null;
    }

    /**
     * Call the appropriate query method based on the filter key.
     */
    /**
     * Handles query method calls based on the provided key and value.
     *
     * @param string $key the query key
     * @param array|string|int $value the query value, which can be an array, string, or int depending on the key
     * @return string|null the resulting string for the filter or null if the key is unsupported
     */
    protected function callQueryMethod(string $key, array|string|int $value): ?string
    {
        return match ($key) {
            'location_ids' => is_array($value) ? $this->locationQueries->getLocationForFilter($value) : null,
            'location_id', 'compare_location_id' => $this->locationQueries->getLocationForFilter([$value]),
            'product_id' => $this->productQueries->getProductNameForFilter([$value]),
            'product_collection_id' => $this->productCollectionQueries->getProductCollectionNameForFilter([$value]),
            'category_ids' => is_array($value) ? $this->categoryQueries->getCategoryNameForFilter($value) : null,
            'brand_ids' => is_array($value) ? $this->brandQueries->getBrandNameForFilter($value) : null,
            'promoter_ids', 'promoter_id' => is_array($value) ? $this->promoterQueries->getByIdsWithName(
                $value
            ) : $this->promoterQueries->getByIdsWithName([$value]),
            'group_ids' => is_array($value) ? $this->promoterGroupQueries->getPromoterGroupNameForFilter($value) : null,
            'size_ids' => is_array($value) ? $this->sizeQueries->getSizeNameForFilter($value) : null,
            'color_ids' => is_array($value) ? $this->colorQueries->getColorNameForFilter($value) : null,
            'department_ids' => is_array($value) ? $this->departmentQueries->getDepartmentNameForFilter($value) : null,
            'article_numbers' => is_array($value) ? implode(', ', $value) : null,
            'tag_ids' => is_array($value) ? $this->tagQueries->getTagsNameForFilter($value) : null,
            'style_ids' => is_array($value) ? $this->styleQueries->getStyleNameForFilter($value) : null,
            'age_of_product_type' => is_int($value) ? AgeOfProductTypes::getFormattedCaseName($value) : null,
            'age_category_id' => is_int($value) ? AgeCategories::getFormattedCaseName($value) : null,
            'last_selling_date_range' => is_array($value) && count($value) >= 2 ? $value[0] . ' - ' . $value[1] : null,
            'counter_ids' => is_array($value) ? $this->counterQueries->getCounterNameForFilter($value) : null,
            'region_ids' => is_array($value) ? $this->regionQueries->getRegionNameForFilter($value) : null,
            'region_id', 'compare_region_id' => 0 == $value ? 'ALL REGIONS' : $this->regionQueries->getRegionNameForFilter(
                [$value]
            ),
            'article_number' => is_string($value) ? $value : null,
            'date_range' => is_array($value) && count($value) === 2 ? $value[0] . ' - ' . $value[1] : null,
            'employee_id' => $this->employeeQueries->getEmployeeNameForFilter((int) $value),
            'module_type' => is_string($value) ? ModelMappingTypes::getFormattedCaseName((int) $value) : null,
            'report_type' => is_string($value) ? ReportTypes::getFormattedCaseName((int) $value) : null,
            'date' => is_string($value) ? $value : null,
            'grade_filter' => is_int($value) ? $this->saleThroughRatioQueries->getGradeNameForFilter($value) : null,
            'location_type', 'compare_location_type' => is_string($value) ? $value : null,
            'type_id' => is_string($value) ? OrderTypes::getFormattedCaseName((int) $value) : null,
            default => null,
            'member_id' => $this->memberQueries->getMemberNameForFilter((int) $value),
            'e_invoice_submitted' => $value ? 'Yes' : 'No',
        };
    }
}
