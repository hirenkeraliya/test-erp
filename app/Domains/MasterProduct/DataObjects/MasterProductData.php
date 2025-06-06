<?php

declare(strict_types=1);

namespace App\Domains\MasterProduct\DataObjects;

use App\Domains\Attribute\AttributeQueries;
use App\Domains\Category\CategoryQueries;
use App\Domains\Department\DepartmentQueries;
use App\Domains\MasterProduct\MasterProductQueries;
use App\Domains\MasterProduct\Rules\ProductVariantSelectedValueRequire;
use App\Domains\MasterProduct\Rules\SaleChannelRequired;
use App\Domains\MasterProduct\Rules\UniqueCodeUpcVariant;
use App\Domains\Membership\MembershipQueries;
use App\Domains\PackageType\PackageTypeQueries;
use App\Domains\Product\DataObjects\ProductVariantData;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Product\Rules\CustomFieldValue;
use App\Domains\Product\Rules\DoesAttributeExist;
use App\Domains\Tag\TagQueries;
use App\Domains\UnitOfMeasure\UnitOfMeasureQueries;
use App\Domains\Vendor\VendorQueries;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Rules\In;
use Illuminate\Validation\Rules\Unique;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class MasterProductData extends Data
{
    public function __construct(
        public int $brand_id,
        public ?int $variant_template_id,
        public string $name,
        public ?string $code,
        public ?string $description,
        public ?int $department_id,
        public ?int $vendor_id,
        public ?int $unit_of_measure_id,
        public ?string $article_number,
        public string $type_id,
        public bool $has_batch,
        public array $category_ids,
        public bool $is_non_inventory,
        public bool $is_non_selling_item,
        public ?UploadedFile $thumbnail,
        public ?string $original_created_at = null,
        public ?array $images = [],
        public ?array $videos = [],
        public ?array $attached_templates = [],
        public ?array $custom_field_values = [],
        public ?array $assembly_child_master_products = null,
        public ?array $tag_ids = null,
        #[DataCollectionOf(ProductVariantData::class)]
        public ?DataCollection $variants = null,
    ) {
    }

    /**
     * @return array<string, array<(Exists|In|Unique|string)>>
     */
    public static function rules(Request $request): array
    {
        $masterProductId = null;
        $masterProductQueries = new MasterProductQueries();
        $unitOfMeasureQueries = new UnitOfMeasureQueries();
        $categoryQueries = new CategoryQueries();
        $attributeQueries = resolve(AttributeQueries::class);
        $vendorQueries = resolve(VendorQueries::class);
        $tagQueries = resolve(TagQueries::class);
        $membershipQueries = resolve(MembershipQueries::class);
        $packageTypeQueries = resolve(PackageTypeQueries::class);
        $departmentQueries = resolve(DepartmentQueries::class);

        if ('admin.master_products.update' === $request->route()?->getName() || 'admin.draft_products.update_master_product' === $request->route()?->getName()) {
            $masterProductId = $request->route()->parameter('masterProductId');
        }

        $validateRecords = [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'code' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('master_products', 'code')->ignore($masterProductId)
                    ->where($masterProductQueries->filterByCompany(session('admin_company_id'))),
            ],
            'unit_of_measure_id' => [
                'nullable',
                'integer',
                Rule::exists('unit_of_measures', 'id')
                    ->where($unitOfMeasureQueries->filterByCompany(session('admin_company_id'))),
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
            'brand_id' => ['required', 'integer'],
            'variant_template_id' => [
                'required_if:type_id,' . ProductTypes::REGULAR_PRODUCT->value,
                'nullable',
                'integer',
            ],
            'category_ids' => [
                'required',
                'array',
                Rule::exists('categories', 'id')
                    ->where($categoryQueries->filterByCompany(session('admin_company_id'))),
            ],
            'tag_ids' => [
                'nullable',
                'array',
                Rule::exists('tags', 'id')
                    ->where($tagQueries->filterByCompany(session('admin_company_id'))),
            ],
            'article_number' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('master_products', 'article_number')->ignore($masterProductId)
                    ->where($masterProductQueries->filterByCompany(session('admin_company_id'))),
            ],
            'has_batch' => ['required', 'boolean'],
            'type_id' => ['required', 'integer', 'in:' . ProductTypes::getValues()],
            'images' => ['array', 'nullable'],
            'images.*' => [
                'required',
                'file',
                'mimetypes:image/jpeg,image/png',
                File::image()->dimensions(Rule::dimensions()->maxWidth(500)->maxHeight(500)),
            ],
            'videos' => ['array', 'nullable'],
            'videos.*' => ['required', 'file', 'mimetypes:video/mp4,video/avi,video/mpeg', 'max:15000'],
            'is_non_inventory' => ['required', 'boolean'],
            'is_non_selling_item' => ['required', 'boolean'],
            'original_created_at' => ['nullable', 'date', 'max:255'],
            'thumbnail' => [
                'nullable',
                'file',
                'mimetypes:image/jpeg,image/gif,image/png',
                File::image()->dimensions(Rule::dimensions()->maxWidth(343)->maxHeight(260)),
            ],
            'attached_templates' => ['nullable', 'array'],
            'attached_templates.*.template_id' => ['required'],
            'custom_field_values' => ['nullable', 'array'],
            'custom_field_values.*.id' => ['required'],
            'custom_field_values.*.attributes.*.id' => [new DoesAttributeExist($attributeQueries, $request)],
            'custom_field_values.*.attributes.*.selected_value' => [
                new CustomFieldValue($attributeQueries, $request),
            ],
            'variants' => ['nullable', 'array'],
            'variants.*.name' => ['required', 'string', 'max:255'],
            'variants.*.code' => ['nullable', 'string', 'max:255', new UniqueCodeUpcVariant('code', 'id')],
            'variants.*.description' => ['nullable', 'string'],
            'variants.*.upc' => ['required', 'alpha_num', 'max:255', new UniqueCodeUpcVariant('upc', 'id')],
            'variants.*.ean' => ['nullable', 'string', 'max:255'],
            'variants.*.custom_sku' => ['nullable', 'string', 'max:255'],
            'variants.*.manufacturer_sku' => ['nullable', 'string', 'max:255'],
            'variants.*.retail_price' => ['nullable', 'numeric', 'between:0,99999999.99'],
            'variants.*.wholesale_price' => ['nullable', 'numeric', 'between:0,99999999.99'],
            'variants.*.staff_price' => ['nullable', 'numeric', 'between:0,99999999.99'],
            'variants.*.minimum_price' => ['nullable', 'numeric', 'between:0,99999999.99'],
            'variants.*.purchase_cost' => ['nullable', 'numeric', 'between:0,99999999.99'],
            'variants.*.online_price' => ['nullable', 'numeric', 'between:0,99999999.99'],
            'variants.*.is_temporarily_unavailable' => ['required', 'boolean'],
            'variants.*.is_available_in_pos' => ['required', 'boolean'],
            'variants.*.is_available_in_ecommerce' => ['required', 'boolean'],
            'variants.*.is_sold_as_single_item' => ['required', 'boolean'],
            'variants.*.thumbnail' => [
                'nullable',
                'file',
                'mimetypes:image/jpeg,image/gif,image/png',
                File::image()->dimensions(Rule::dimensions()->maxWidth(343)->maxHeight(260)),
            ],
            'variants.*.images' => ['array', 'nullable'],
            'variants.*.images.*' => [
                'required',
                'file',
                'mimetypes:image/jpeg,image/png',
                File::image()->dimensions(Rule::dimensions()->maxWidth(500)->maxHeight(500)),
            ],
            'variants.*.videos' => ['array', 'nullable'],
            'variants.*.videos.*' => ['required', 'file', 'mimetypes:video/mp4,video/avi,video/mpeg', 'max:15000'],
            'variants.*.tiers' => ['nullable', 'array'],
            'variants.*.tiers.*.points' => ['required', 'integer'],
            'variants.*.tiers.*.membership_id' => [
                'required',
                'integer',
                Rule::exists('memberships', 'id')
                    ->where($membershipQueries->filterByCompany(session('admin_company_id'))),
            ],
            'variants.*.product_variant_values' => ['nullable', 'array'],
            'variants.*.product_variant_values.*.id' => ['nullable', 'integer'],
            'variants.*.product_variant_values.*.selected_value' => [
                new ProductVariantSelectedValueRequire('selected_value'),
            ],
            'variants.*.sale_channel_ids' => [
                'nullable',
                'array',
                new SaleChannelRequired($request->input('variants')),
            ],
            'variants.*.sale_channel_ids.*' => ['integer'],
        ];
        if ($request->type_id === ProductTypes::ASSEMBLY_PRODUCT->value) {
            $validateRecords['assembly_child_master_products'] = ['required', 'nullable', 'array'];
            $validateRecords['assembly_child_master_products.*.units'] = [
                'required',
                'numeric',
                'between:0.01,99999999.99',
            ];
            $validateRecords['assembly_child_master_products.*.child_master_product_id'] = [
                'required',
                'integer',
                'distinct:strict',
            ];
        }

        if ($request->type_id === ProductTypes::REGULAR_PRODUCT->value) {
            $validateRecords['variants.*.boxes'] = ['nullable', 'array'];
            $validateRecords['variants.*.boxes.*.units'] = ['required', 'numeric', 'min:1'];
            $validateRecords['variants.*.boxes.*.package_type_id'] = [
                'required',
                'integer',
                Rule::exists('package_types', 'id')
                    ->where($packageTypeQueries->filterByCompany(session('admin_company_id'))),
            ];
            $validateRecords['variants.*.boxes.*.retail_price'] = ['nullable', 'numeric', 'between:0,99999999.99'];
            $validateRecords['variants.*.boxes.*.minimum_price'] = ['nullable', 'numeric', 'between:0,99999999.99'];
            $validateRecords['variants.*.boxes.*.staff_price'] = ['nullable', 'numeric', 'between:0,99999999.99'];
            $validateRecords['variants.*.boxes.*.purchase_cost'] = ['nullable', 'numeric', 'between:0,99999999.99'];
            $validateRecords['variants.*.boxes.*.wholesale_price'] = ['nullable', 'numeric', 'between:0,99999999.99'];
            $validateRecords['variants.*.boxes.*.box_product_loyalty_points'] = ['nullable', 'array'];
            $validateRecords['variants.*.boxes.*.box_product_loyalty_points.*.membership_id'] = [
                'required',
                'integer',
                Rule::exists('memberships', 'id')
                    ->where($membershipQueries->filterByCompany(session('admin_company_id'))),
            ];
            $validateRecords['variants.*.boxes.*.box_product_loyalty_points.*.points'] = ['required', 'integer'];
        }

        return $validateRecords;
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'variants.*.name' => 'Name field is required',
            'variants.*.retail_price' => 'Retail price should not be more than 10 digits.',
            'variants.*.wholesale_price' => 'Wholesale price should not be more than 10 digits.',
            'variants.*.staff_price' => 'Staff price should not be more than 10 digits.',
            'variants.*.minimum_price' => 'Minimum price should not be more than 10 digits.',
            'variants.*.purchase_cost' => 'Purchase cost should not be more than 10 digits.',
            'variants.*.is_temporarily_unavailable' => 'Temporarily unavailable is required',
            'variants.*.is_available_in_pos' => 'Available in pos is required',
            'variants.*.is_available_in_ecommerce' => 'Available in ecommerce is required',
            'variants.*.is_sold_as_single_item' => 'Sold as single item is required',
            'variants.*.tiers.*.points' => 'Loyalty Point field is required.',
            'variants.*.item_variant_values.*.id' => 'Attribute field is required.',
            'variants.*.boxes.*.units' => 'Unit field is required',
            'variants.*.boxes.*.package_type_id' => 'Package type field is required',
            'variants.*.boxes.*.retail_price' => 'Retail Price should not be more than 10 digits.',
            'variants.*.boxes.*.minimum_price' => 'Minimum Price should not be more than 10 digits.',
            'variants.*.boxes.*.staff_price' => 'Staff Price should not be more than 10 digits.',
            'variants.*.boxes.*.purchase_cost' => 'Purchase Cost should not be more than 10 digits.',
            'variants.*.boxes.*.wholesale_price' => 'Wholesale Price should not be more than 10 digits.',
            'variants.*.boxes.*.box_product_loyalty_points.*.membership_id' => 'Membership field is required.',
            'variants.*.boxes.*.box_product_loyalty_points.*.points' => 'Loyalty Point field is required.',
        ];
    }
}
