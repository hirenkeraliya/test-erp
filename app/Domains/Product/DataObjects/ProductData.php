<?php

declare(strict_types=1);

namespace App\Domains\Product\DataObjects;

use App\Domains\Attribute\AttributeQueries;
use App\Domains\Category\CategoryQueries;
use App\Domains\Color\ColorQueries;
use App\Domains\Department\DepartmentQueries;
use App\Domains\Membership\MembershipQueries;
use App\Domains\PackageType\PackageTypeQueries;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Product\ProductQueries;
use App\Domains\Product\Rules\CustomFieldValue;
use App\Domains\Product\Rules\DoesAttributeExist;
use App\Domains\Product\SubDepartment\Enums\SubDepartments;
use App\Domains\RetailPlanningHierarchy\RetailPlanningHierarchyQueries;
use App\Domains\Season\SeasonQueries;
use App\Domains\Size\SizeQueries;
use App\Domains\Style\StyleQueries;
use App\Domains\Tag\TagQueries;
use App\Domains\UnitOfMeasure\UnitOfMeasureQueries;
use App\Domains\Vendor\VendorQueries;
use App\Services\RetailPlanningService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Rules\In;
use Illuminate\Validation\Rules\Unique;
use Spatie\LaravelData\Data;

class ProductData extends Data
{
    public function __construct(
        public string $name,
        public ?string $description,
        public ?string $code,
        public ?int $unit_of_measure_id,
        public ?int $season_id,
        public ?int $department_id,
        public ?int $sub_department_id,
        public ?int $color_id,
        public ?int $vendor_id,
        public ?int $size_id,
        public int $brand_id,
        public ?int $style_id,
        public string $upc,
        public ?string $verification_qr_code,
        public ?string $ean,
        public ?string $custom_sku,
        public ?string $manufacturer_sku,
        public ?string $article_number,
        public ?float $retail_price,
        public ?float $franchise_price_1,
        public ?float $franchise_price_2,
        public ?float $franchise_price_3,
        public ?float $wholesale_price,
        public ?float $company_or_tender_price,
        public ?float $branch_price,
        public ?float $minimum_price,
        public ?float $original_capital_price,
        public ?float $capital_price,
        public ?float $staff_price,
        public ?float $purchase_cost,
        public ?float $online_price,
        public bool $is_temporarily_unavailable,
        public bool $has_batch,
        public string $type_id,
        public array $category_ids,
        public bool $is_non_inventory,
        public bool $is_non_selling_item,
        public ?UploadedFile $thumbnail,
        public bool $is_available_in_pos,
        public bool $is_available_in_ecommerce,
        public bool $is_warranty,
        public bool $is_sold_as_single_item,
        public bool $sell_item_via_derivative,
        public ?string $original_created_at = null,
        public ?array $images = [],
        public ?array $tag_ids = null,
        public ?array $tiers = null,
        public ?array $assembly_child_products = null,
        public ?array $boxes = null,
        public ?array $videos = [],
        public ?array $attached_templates = [],
        public ?array $custom_field_values = [],
        public ?int $retail_planning_hierarchy_id = null,
        public ?string $warranty_month = null,
        public ?array $sale_channel_ids = null,
        public ?int $height = 0,
        public ?int $width = 0,
        public ?int $weight = 0,
    ) {
    }

    /**
     * @return array<string, array<(Exists|In|Unique|string)>>
     */
    public static function rules(Request $request): array
    {
        $productId = null;
        $productQueries = new ProductQueries();
        $unitOfMeasureQueries = new UnitOfMeasureQueries();
        $seasonQueries = new SeasonQueries();
        $departmentQueries = new DepartmentQueries();
        $colorQueries = new ColorQueries();
        $sizeQueries = new SizeQueries();
        $styleQueries = new StyleQueries();
        $tagQueries = new TagQueries();
        $categoryQueries = new CategoryQueries();
        $membershipQueries = new MembershipQueries();
        $packageTypeQueries = resolve(PackageTypeQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $retailPlanningService = resolve(RetailPlanningService::class);
        $vendorQueries = resolve(VendorQueries::class);

        if ('admin.products.update' === $request->route()?->getName() || 'admin.draft_products.update' === $request->route()?->getName()) {
            $productId = $request->route()->parameter('productId');
        }

        $validateRecords = [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'code' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('products', 'code')->ignore($productId)
                    ->where($productQueries->filterByCompany(session('admin_company_id'))),
            ],
            'unit_of_measure_id' => [
                'nullable',
                'integer',
                Rule::exists('unit_of_measures', 'id')
                    ->where($unitOfMeasureQueries->filterByCompany(session('admin_company_id'))),
            ],
            'season_id' => [
                'nullable',
                'integer',
                Rule::exists('seasons', 'id')
                    ->where($seasonQueries->filterByCompany(session('admin_company_id'))),
            ],
            'department_id' => [
                'nullable',
                'integer',
                Rule::exists('departments', 'id')
                    ->where($departmentQueries->filterByCompany(session('admin_company_id'))),
            ],
            'vendor_id' => [
                'nullable',
                'integer',
                Rule::exists('vendors', 'id')
                    ->where($vendorQueries->filterByCompany(session('admin_company_id'))),
            ],
            'sub_department_id' => ['nullable', 'integer', 'in:' . SubDepartments::getValues()],
            'color_id' => [
                'nullable',
                'integer',
                Rule::exists('colors', 'id')
                    ->where($colorQueries->filterByCompany(session('admin_company_id'))),
            ],
            'size_id' => [
                'nullable',
                'integer',
                Rule::exists('sizes', 'id')
                    ->where($sizeQueries->filterByCompany(session('admin_company_id'))),
            ],
            'brand_id' => ['required', 'integer'],
            'style_id' => [
                'nullable',
                'integer',
                Rule::exists('styles', 'id')
                    ->where($styleQueries->filterByCompany(session('admin_company_id'))),
            ],
            'upc' => [
                'required',
                'alpha_num',
                'max:255',
                Rule::unique('products', 'upc')->ignore($productId)
                    ->where($productQueries->filterByCompany(session('admin_company_id'))),
            ],
            'verification_qr_code' => [
                'nullable',
                'alpha_num',
                'max:25',
                Rule::unique('products', 'verification_qr_code')->ignore($productId)
                    ->where($productQueries->filterByCompany(session('admin_company_id'))),
            ],
            'tag_ids' => [
                'nullable',
                'array',
                Rule::exists('tags', 'id')
                    ->where($tagQueries->filterByCompany(session('admin_company_id'))),
            ],
            'category_ids' => [
                'required',
                'array',
                Rule::exists('categories', 'id')
                    ->where($categoryQueries->filterByCompany(session('admin_company_id'))),
            ],
            'is_warranty' => ['required', 'boolean'],
            'warranty_month' => ['required_if:is_warranty,true', 'nullable', 'integer', 'min:1', 'max:999'],
            'ean' => ['nullable', 'string', 'max:255'],
            'custom_sku' => ['nullable', 'string', 'max:255'],
            'manufacturer_sku' => ['nullable', 'string', 'max:255'],
            'article_number' => ['nullable', 'string', 'max:255'],
            'retail_price' => ['nullable', 'numeric', 'between:0,99999999.99'],
            'franchise_price_1' => ['nullable', 'numeric', 'between:0,99999999.99'],
            'franchise_price_2' => ['nullable', 'numeric', 'between:0,99999999.99'],
            'franchise_price_3' => ['nullable', 'numeric', 'between:0,99999999.99'],
            'wholesale_price' => ['nullable', 'numeric', 'between:0,99999999.99'],
            'company_or_tender_price' => ['nullable', 'numeric', 'between:0,99999999.99'],
            'branch_price' => ['nullable', 'numeric', 'between:0,99999999.99'],
            'minimum_price' => [
                'nullable',
                'required_if:type_id,' . ProductTypes::SPECIAL_ORDER->value,
                'required_if:type_id,' . ProductTypes::CUSTOM_ORDER->value,
                'required_if:type_id,' . ProductTypes::POSTAGE_COST->value,
                'numeric',
                'between:0,99999999.99',
            ],
            'original_capital_price' => ['nullable', 'numeric', 'between:0,99999999.99'],
            'capital_price' => ['nullable', 'numeric', 'between:0,99999999.99'],
            'staff_price' => ['nullable', 'numeric', 'between:0,99999999.99'],
            'purchase_cost' => ['nullable', 'numeric', 'between:0,99999999.99'],
            'online_price' => ['nullable', 'numeric', 'between:0,99999999.99'],
            'is_temporarily_unavailable' => ['required', 'boolean'],
            'has_batch' => ['required', 'boolean'],
            'type_id' => ['required', 'integer', 'in:' . ProductTypes::getValues()],
            'images' => ['array', 'nullable'],
            'images.*' => [
                'required',
                'file',
                'mimetypes:image/jpeg,image/png',
                File::image()->dimensions(Rule::dimensions()->maxWidth(500)->maxHeight(500)),
                'max:' . config('services.max_upload_size'),
            ],
            'videos' => ['array', 'nullable'],
            'videos.*' => [
                'required',
                'file',
                'mimetypes:video/mp4,video/avi,video/mpeg',
                'max:' . config('services.max_upload_size'),
            ],
            'is_non_inventory' => ['required', 'boolean'],
            'is_non_selling_item' => ['required', 'boolean'],
            'is_available_in_pos' => ['required', 'boolean'],
            'is_available_in_ecommerce' => ['required', 'boolean'],
            'is_sold_as_single_item' => ['required', 'boolean'],
            'sell_item_via_derivative' => ['required', 'boolean'],
            'original_created_at' => ['nullable', 'date', 'max:255'],
            'tiers' => ['nullable', 'array'],
            'tiers.*.points' => ['required', 'integer'],
            'tiers.*.membership_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('memberships', 'id')
                    ->where($membershipQueries->filterByCompany(session('admin_company_id'))),
            ],
            'thumbnail' => [
                'nullable',
                'file',
                'mimetypes:image/jpeg,image/gif,image/png',
                File::image()->dimensions(Rule::dimensions()->maxWidth(343)->maxHeight(260)),
                'max:' . config('services.max_upload_size'),
            ],
            'attached_templates' => ['nullable', 'array'],
            'attached_templates.*.template_id' => ['required'],
            'custom_field_values' => ['nullable', 'array'],
            'custom_field_values.*.id' => ['required'],
            'custom_field_values.*.attributes.*.id' => [new DoesAttributeExist($attributeQueries, $request)],
            'custom_field_values.*.attributes.*.selected_value' => [
                new CustomFieldValue($attributeQueries, $request),
            ],
            'sale_channel_ids' => ['required_if:is_available_in_ecommerce,true', 'nullable', 'array'],
            'sale_channel_ids.*' => ['integer'],
        ];

        if ($request->type_id === ProductTypes::REGULAR_PRODUCT->value || $request->type_id === ProductTypes::SERIAL_PRODUCT->value) {
            $validateRecords['boxes'] = ['nullable', 'array'];
            $validateRecords['boxes.*.units'] = ['required', 'numeric', 'min:1'];
            $validateRecords['boxes.*.package_type_id'] = [
                'required',
                'integer',
                Rule::exists('package_types', 'id')
                    ->where($packageTypeQueries->filterByCompany(session('admin_company_id'))),
            ];
            $validateRecords['boxes.*.retail_price'] = ['nullable', 'numeric', 'between:0,99999999.99'];
            $validateRecords['boxes.*.staff_price'] = ['nullable', 'numeric', 'between:0,99999999.99'];
            $validateRecords['boxes.*.box_product_loyalty_points'] = ['nullable', 'array'];
            $validateRecords['boxes.*.box_product_loyalty_points.*.membership_id'] = [
                'required',
                'integer',
                Rule::exists('memberships', 'id')
                    ->where($membershipQueries->filterByCompany(session('admin_company_id'))),
            ];
            $validateRecords['boxes.*.box_product_loyalty_points.*.points'] = ['required', 'integer'];
        }

        if ($request->type_id === ProductTypes::ASSEMBLY_PRODUCT->value) {
            $validateRecords['assembly_child_products'] = ['required', 'nullable', 'array'];
            $validateRecords['assembly_child_products.*.units'] = ['required', 'numeric', 'between:0.01,99999999.99'];
            $validateRecords['assembly_child_products.*.child_product_id'] = ['required', 'integer', 'distinct:strict'];
        }

        if ($retailPlanningService->isConfigured()) {
            $retailPlanningHierarchyQueries = resolve(RetailPlanningHierarchyQueries::class);
            $validateRecords['retail_planning_hierarchy_id'] = [
                'nullable',
                'integer',
                Rule::exists('retail_planning_hierarchies', 'id')
                    ->where($retailPlanningHierarchyQueries->filterByCompany(session('admin_company_id'))),
            ];
        }

        return $validateRecords;
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'retail_price.between' => 'Retail price should not be more than 10 digits.',
            'franchise_price_1' => 'Franchise price 1 should not be more than 10 digits.',
            'franchise_price_2' => 'Franchise price 2 should not be more than 10 digits.',
            'franchise_price_3' => 'Franchise price 3 should not be more than 10 digits.',
            'wholesale_price' => 'Wholesale price should not be more than 10 digits.',
            'company_or_tender_price' => 'Company or tender price should not be more than 10 digits.',
            'branch_price' => 'Branch price should not be more than 10 digits.',
            'minimum_price.between' => 'Minimum price should not be more than 10 digits.',
            'minimum_price.required_unless' => 'Minimum price is required for the non-regular products.',
            'original_capital_price' => 'Original capital price should not be more than 10 digits.',
            'capital_price' => 'Capital price should not be more than 10 digits.',
            'purchase_cost' => 'Purchase cost should not be more than 10 digits.',
            'boxes.*.box_product_loyalty_points.*.membership_id' => 'Membership field is required.',
            'boxes.*.box_product_loyalty_points.*.points' => 'Loyalty Point field is required.',
        ];
    }
}
