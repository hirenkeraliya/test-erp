<?php

declare(strict_types=1);

use App\Domains\Product\DataObjects\ProductData;
use App\Models\Brand;
use App\Models\Company;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->company = Company::factory()->create();

    $this->productA = Product::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Test Name',
        'code' => '123456',
        'upc' => 'xyz12',
    ]);
});

test(
    'user cannot add product with same UPC, or code for the same company.',
    function (string $name, string $code, string $upc): void {
        setCompanyIdInSession($this->company->id);

        $productDetails = getProductDetail($name, $code, $upc, $this->company->id, []);

        $request = new Request($productDetails);

        $request->validate(ProductData::rules($request));
    }
)->with([['XYZ', '123456', '1100554454'], ['WXYZ', '1234567', 'xyz12']])->throws(ValidationException::class);

function getProductDetail(
    string $name,
    string $code,
    string $upc,
    int $companyId,
    array $categoryIds,
    array $customFieldValues = [],
    array $attachedTemplates = [],
): array {
    $brandId = Brand::factory()->create()->id;

    return Product::factory()->make([
        'company_id' => $companyId,
        'name' => $name,
        'description' => null,
        'code' => $code,
        'upc' => $upc,
        'unit_of_measure_id' => null,
        'season_id' => null,
        'department_id' => null,
        'color_id' => null,
        'size_id' => null,
        'brand_id' => $brandId,
        'style_id' => null,
        'category_ids' => $categoryIds,
        'is_warranty' => false,
        'custom_field_values' => $customFieldValues,
        'attached_templates' => $attachedTemplates,
        'is_available_in_ecommerce' => false,
        'sale_channel_ids' => [],
    ])->toArray();
}
