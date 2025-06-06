<?php

declare(strict_types=1);

use App\Domains\Attribute\Enums\FieldType;
use App\Domains\Product\DataObjects\ProductData;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Company;
use App\Models\Department;
use App\Models\Product;
use App\Models\Season;
use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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

        $productDetails = getProductDetails($name, $code, $upc, $this->company->id, []);

        $request = new Request($productDetails);

        $request->validate(ProductData::rules($request));
    }
)->with([['XYZ', '123456', '1100554454'], ['WXYZ', '1234567', 'xyz12']])->throws(ValidationException::class);

test('user can add a product with the same name for the different company.', function (): void {
    $companyB = Company::factory()->create();
    $category = Category::factory()->create([
        'company_id' => $companyB->id,
    ]);
    setCompanyIdInSession($companyB->id);
    $productDetails = getProductDetails('Test Name', '123456', 'xyz12', $companyB->id, [$category->id]);
    $request = new Request($productDetails);
    $request->validate(ProductData::rules($request));
    $this->assertTrue(true);
});

test(
    'the product will not be added when the selected season and department is from a different company.',
    function ($sessionId, $departmentId): void {
        setCompanyIdInSession($this->company->id);

        $productDetails = Product::factory()->make([
            'name' => 'XYZ',
            'code' => 'ABC123',
            'upc' => 'XYZ123',
            'unit_of_measure_id' => null,
            'season_id' => $sessionId,
            'department_id' => $departmentId,
            'color_id' => null,
            'size_id' => null,
            'style_id' => null,
        ])->toArray();

        $request = new Request($productDetails);

        $request->validate(ProductData::rules($request));
    }
)->with([
    [
        fn () => Season::factory()->create()->id,
        fn () => Department::factory()->create()->id,
    ],
    [
        fn () => Season::factory()->create()->id,
        fn () => Department::factory()->create()->id,
    ],
])->throws(ValidationException::class);

describe('Custom Field Value Validations', function (): void {
    beforeEach(function (): void {
        $category = Category::factory()->create([
            'company_id' => $this->company->id,
        ]);
        setCompanyIdInSession($this->company->id);
        $this->productDetails = getProductDetails(
            'Test Name',
            fake()->regexify('[A-Z]{5}[0-4]{3}'),
            fake()->regexify('[A-Z]{5}[0-4]{3}'),
            $this->company->id,
            [$category->id]);
        $this->templateOne = Template::factory()->create([
            'company_id' => $this->company->id,
        ]);
    });

    function createAttribute(
        $templateId,
        $fieldType,
        $defaultValue,
        $isRequired,
        $from = null,
        $to = null,
        $options = null,
        $selectedValue = null
    ) {
        $attribute = Attribute::factory()->create([
            'field_type' => $fieldType,
            'default_value' => $defaultValue,
            'is_required' => $isRequired,
            'from' => $from,
            'to' => $to,
            'options' => $options,
        ]);

        $attribute->templates()->attach($templateId);

        return $attribute;
    }

    function prepareCustomFieldValues($template, $selectedValue): array
    {
        $attributes = transformAttributes($template->attributes, $selectedValue);

        return [[
            'id' => $template->id,
            'name' => $template->name,
            'attributes' => $attributes->toArray(),
        ]];
    }

    function transformAttributes(Collection $attributes, $selectedValue): Collection
    {
        return $attributes->map(fn ($attribute): array => [
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
            'selected_value' => $selectedValue,
        ]);
    }

    test(
        'throws error for fieldtype',
        function ($fieldType, $defaultValue, $isRequired, $selectedValue, $from, $to, $options): void {
            $this->templateOne = Template::factory()->create([
                'company_id' => $this->company->id,
            ]);

            $this->attribute = createAttribute(
                templateId: $this->templateOne->id,
                fieldType: $fieldType,
                defaultValue: $defaultValue,
                isRequired: $isRequired,
                from: $from,
                to: $to,
                options: $options,
            );

            $this->productDetails['custom_field_values'] = prepareCustomFieldValues($this->templateOne, $selectedValue);

            $this->productDetails['attached_templates'] = [[
                'template_id' => $this->templateOne->id,
            ]];

            $request = new Request($this->productDetails);

            $request->validate(ProductData::rules($request));
        }
    )->with([
        'toggle with improper value' => [FieldType::TOGGLE->value, true, true, 'some fail value', null, null, null],
        'text with required true but no selected value' => [
            FieldType::TEXT->value,
            null,
            true,
            null,
            null,
            null,
            null,
        ],
        'number with value not present in between' => [FieldType::NUMBER->value, null, true, 5, 6, 10, null],
        'number with value with decimals' => [FieldType::NUMBER->value, null, true, 5.678, 6, 10, null],
        'decimal with value not present in between' => [FieldType::DECIMAL->value, null, true, 5, 5.70, 5.80, null],
        'date with value not present in between' => [
            FieldType::DATE->value,
            null,
            true,
            '2024-06-12',
            '2024-06-13',
            '2024-06-14',
            null,
        ],
        'date with improper value' => [FieldType::DATE->value, null, true, 'ldldldl', '2024-06-13', '2024-06-14', null],
        'datetime with value not present in between' => [
            FieldType::DATETIME->value,
            null,
            true,
            '2024-06-21 14:32:00',
            '2024-06-21 14:30:00',
            '2024-06-21 14:31:00',
            null,
        ],
        'datetime with improper value' => [
            FieldType::DATETIME->value,
            null,
            true,
            'improper value',
            '2024-06-21 14:30:00',
            '2024-06-21 14:31:00',
            null,
        ],
        'select with improper value' => [
            FieldType::SELECT->value,
            null,
            true,
            'improper value',
            null,
            null,
            ['option 1', 'option 2', 'option 3'],
        ],
        'multi select with improper value' => [
            FieldType::MULTISELECT->value,
            null,
            true,
            ['improper value 1', 'improper value 2'],
            null,
            null,
            ['option 1', 'option 2', 'option 3'],
        ],
    ])->throws(ValidationException::class);

    test(
        'throws no error for fieldtype',
        function ($fieldType, $defaultValue, $isRequired, $selectedValue, $from, $to, $options): void {
            $this->templateOne = Template::factory()->create([
                'company_id' => $this->company->id,
            ]);

            $this->attribute = createAttribute(
                templateId: $this->templateOne->id,
                fieldType: $fieldType,
                defaultValue: $defaultValue,
                isRequired: $isRequired,
                from: $from,
                to: $to,
                options: $options,
            );

            $this->productDetails['custom_field_values'] = prepareCustomFieldValues($this->templateOne, $selectedValue);

            $this->productDetails['attached_templates'] = [[
                'template_id' => $this->templateOne->id,
            ]];

            $request = new Request($this->productDetails);

            $request->validate(ProductData::rules($request));
        }
    )->with([
        'toggle' => [FieldType::TOGGLE->value, true, true, true, null, null, null],
        'text' => [FieldType::TEXT->value, null, true, 'some text', null, null, null],
        'number' => [FieldType::NUMBER->value, null, true, 5, 5, 10, null],
        'decimal' => [FieldType::DECIMAL->value, null, true, 5.75, 5.70, 5.80, null],
        'date' => [FieldType::DATE->value, null, true, '2024-06-12', '2024-06-11', '2024-06-14', null],
        'datetime' => [
            FieldType::DATETIME->value,
            null,
            true,
            '2024-06-21 14:32:00',
            '2024-06-21 14:30:00',
            '2024-06-21 14:33:00',
            null,
        ],
        'select' => [
            FieldType::SELECT->value,
            null,
            true,
            'option 1',
            null,
            null,
            ['option 1', 'option 2', 'option 3'],
        ],
        'multi select' => [
            FieldType::MULTISELECT->value,
            null,
            true,
            ['option 3', 'option 2'],
            null,
            null,
            ['option 1', 'option 2', 'option 3'],
        ],
    ])->throwsNoExceptions();
});

function getProductDetails(
    string $name,
    string $code,
    string $upc,
    int $companyId,
    array $categoryIds,
    array $customFieldValues = [],
    array $attachedTemplates = []
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
