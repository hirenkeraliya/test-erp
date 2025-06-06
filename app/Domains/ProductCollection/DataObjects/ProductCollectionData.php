<?php

declare(strict_types=1);

namespace App\Domains\ProductCollection\DataObjects;

use App\Domains\Product\Enums\ProductTypes;
use App\Domains\ProductCollection\Enums\LogicalConnectorTypes;
use App\Domains\ProductCollectionFilter\Enums\ConditionOperatorTypes;
use App\Domains\ProductCollectionFilter\Enums\FilterTypes;
use App\Domains\ProductCollectionFilter\Enums\VariantFilterTypes;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

class ProductCollectionData extends Data
{
    public function __construct(
        public string $name,
        public int $logical_connector_type_id,
        public array $collection_filter_types,
        public bool $is_available_in_ecommerce = false,
        public ?array $sale_channel_ids = null,
    ) {
    }

    public static function rules(Request $request): array
    {
        if (config('app.product_variant')) {
            return [
                'name' => ['required', 'string', 'max:255'],
                'logical_connector_type_id' => ['required', 'integer', 'in:' . LogicalConnectorTypes::getValues()],
                'collection_filter_types' => ['required', 'array'],
                'collection_filter_types.*.filter_type_id' => [
                    'required',
                    'integer',
                    'distinct',
                    'in:' . VariantFilterTypes::getValues(),
                ],
                'collection_filter_types.*.name' => [
                    'required_if:collection_filter_types.*.filter_type_id,' . VariantFilterTypes::NAME->value,
                    'string',
                    'max:255',
                ],
                'collection_filter_types.*.created_by' => [
                    'required_if:collection_filter_types.*.filter_type_id,' . VariantFilterTypes::CREATED_BY->value,
                    'date',
                    'date_format:Y-m-d',
                ],
                'collection_filter_types.*.price' => [
                    'required_if:collection_filter_types.*.filter_type_id,' . VariantFilterTypes::PRICE->value,
                    'numeric',
                    'between:1,99999999.99',
                ],
                'collection_filter_types.*.is_available_in_pos' => [
                    'required_if:collection_filter_types.*.filter_type_id,' . VariantFilterTypes::IS_AVAILABLE_IN_POS->value,
                    'boolean',
                ],
                'collection_filter_types.*.is_available_in_ecommerce' => [
                    'required_if:collection_filter_types.*.filter_type_id,' . VariantFilterTypes::IS_AVAILABLE_IN_ECOMMERCE->value,
                    'boolean',
                ],
                'collection_filter_types.*.category_ids' => [
                    'required_if:collection_filter_types.*.filter_type_id,' . VariantFilterTypes::CATEGORY->value,
                    'array',
                    Rule::exists('categories', 'id'),
                ],
                'collection_filter_types.*.attributes' => [
                    'required_if:collection_filter_types.*.filter_type_id,' . VariantFilterTypes::ATTRIBUTES->value,
                    'array',
                    function ($attribute, $value, $fail): void {
                        $attributeIds = array_column($value, 'attribute');

                        if (count($attributeIds) !== count(array_unique($attributeIds))) {
                            $fail('Each attribute must be unique.');
                        }

                        foreach ($value as $item) {
                            if (empty($item['attribute'])) {
                                $fail('Each Attribute field is required.');
                            }

                            if ([] === $item['attribute_selected_values']) {
                                $fail('Each Values field is required.');
                            }
                        }
                    },
                ],
                'collection_filter_types.*.attributes.*.attribute' => [
                    'required_if:collection_filter_types.*.filter_type_id,' . VariantFilterTypes::ATTRIBUTES->value,
                    Rule::exists('attributes', 'id'),
                ],
                'collection_filter_types.*.department_ids' => [
                    'required_if:collection_filter_types.*.filter_type_id,' . VariantFilterTypes::DEPARTMENT->value,
                    'array',
                    Rule::exists('departments', 'id'),
                ],
                'collection_filter_types.*.brand_ids' => [
                    'required_if:collection_filter_types.*.filter_type_id,' . VariantFilterTypes::BRAND->value,
                    'array',
                    Rule::exists('brands', 'id'),
                ],
                'collection_filter_types.*.tag_ids' => [
                    'required_if:collection_filter_types.*.filter_type_id,' . VariantFilterTypes::TAG->value,
                    'array',
                    Rule::exists('tags', 'id'),
                ],
                'collection_filter_types.*.type_ids' => [
                    'required_if:collection_filter_types.*.filter_type_id,' . VariantFilterTypes::TYPE->value,
                    'array',
                    'in:' . ProductTypes::getValues(),
                ],
                'collection_filter_types.*.sale_unit_sold' => [
                    'required_if:collection_filter_types.*.filter_type_id,' . VariantFilterTypes::SALE_UNIT_SOLD->value,
                    'numeric',
                    'between:1,99999999.99',
                ],
                'collection_filter_types.*.sale_amount' => [
                    'required_if:collection_filter_types.*.filter_type_id,' . VariantFilterTypes::SALE_AMOUNT->value,
                    'numeric',
                    'between:1,99999999.99',
                ],
                'collection_filter_types.*.order_unit_sold' => [
                    'required_if:collection_filter_types.*.filter_type_id,' . VariantFilterTypes::ORDER_UNIT_SOLD->value,
                    'numeric',
                    'between:1,99999999.99',
                ],
                'collection_filter_types.*.order_amount' => [
                    'required_if:collection_filter_types.*.filter_type_id,' . VariantFilterTypes::ORDER_AMOUNT->value,
                    'numeric',
                    'between:1,99999999.99',
                ],
                'collection_filter_types.*.condition_operator_id' => [
                    'required_if:collection_filter_types.*.filter_type_id,' . VariantFilterTypes::NAME->value . ',collection_filter_types.*.filter_type_id,' . VariantFilterTypes::PRICE->value . ', collection_filter_types.*.filter_type_id,' . VariantFilterTypes::CREATED_BY->value . ', collection_filter_types.*.filter_type_id,' . VariantFilterTypes::SALE_UNIT_SOLD->value . ', collection_filter_types.*.filter_type_id,' . VariantFilterTypes::SALE_AMOUNT->value . ', collection_filter_types.*.filter_type_id,' . VariantFilterTypes::ORDER_UNIT_SOLD->value . ', collection_filter_types.*.filter_type_id,' . VariantFilterTypes::ORDER_AMOUNT->value,
                    'nullable',
                    'integer',
                    'in:' . ConditionOperatorTypes::getValues(),
                ],
            ];
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'is_available_in_ecommerce' => ['required', 'boolean'],
            'sale_channel_ids' => ['required_if:is_available_in_ecommerce,true', 'nullable', 'array'],
            'sale_channel_ids.*' => ['integer'],
            'logical_connector_type_id' => ['required', 'integer', 'in:' . LogicalConnectorTypes::getValues()],
            'collection_filter_types' => ['required', 'array'],
            'collection_filter_types.*.filter_type_id' => [
                'required',
                'integer',
                'distinct',
                'in:' . FilterTypes::getValues(),
            ],
            'collection_filter_types.*.name' => [
                'required_if:collection_filter_types.*.filter_type_id,' . FilterTypes::NAME->value,
                'string',
                'max:255',
            ],
            'collection_filter_types.*.created_by' => [
                'required_if:collection_filter_types.*.filter_type_id,' . FilterTypes::CREATED_BY->value,
                'date',
                'date_format:Y-m-d',
            ],
            'collection_filter_types.*.price' => [
                'required_if:collection_filter_types.*.filter_type_id,' . FilterTypes::PRICE->value,
                'numeric',
                'between:1,99999999.99',
            ],
            'collection_filter_types.*.is_available_in_pos' => [
                'required_if:collection_filter_types.*.filter_type_id,' . FilterTypes::IS_AVAILABLE_IN_POS->value,
                'boolean',
            ],
            'collection_filter_types.*.is_available_in_ecommerce' => [
                'required_if:collection_filter_types.*.filter_type_id,' . FilterTypes::IS_AVAILABLE_IN_ECOMMERCE->value,
                'boolean',
            ],
            'collection_filter_types.*.category_ids' => [
                'required_if:collection_filter_types.*.filter_type_id,' . FilterTypes::CATEGORY->value,
                'array',
                Rule::exists('categories', 'id'),
            ],
            'collection_filter_types.*.season_ids' => [
                'required_if:collection_filter_types.*.filter_type_id,' . FilterTypes::SEASON->value,
                'array',
                Rule::exists('seasons', 'id'),
            ],
            'collection_filter_types.*.department_ids' => [
                'required_if:collection_filter_types.*.filter_type_id,' . FilterTypes::DEPARTMENT->value,
                'array',
                Rule::exists('departments', 'id'),
            ],
            'collection_filter_types.*.color_ids' => [
                'required_if:collection_filter_types.*.filter_type_id,' . FilterTypes::COLOR->value,
                'array',
                Rule::exists('colors', 'id'),
            ],
            'collection_filter_types.*.size_ids' => [
                'required_if:collection_filter_types.*.filter_type_id,' . FilterTypes::SIZE->value,
                'array',
                Rule::exists('sizes', 'id'),
            ],
            'collection_filter_types.*.brand_ids' => [
                'required_if:collection_filter_types.*.filter_type_id,' . FilterTypes::BRAND->value,
                'array',
                Rule::exists('brands', 'id'),
            ],
            'collection_filter_types.*.style_ids' => [
                'required_if:collection_filter_types.*.filter_type_id,' . FilterTypes::STYLE->value,
                'array',
                Rule::exists('styles', 'id'),
            ],
            'collection_filter_types.*.tag_ids' => [
                'required_if:collection_filter_types.*.filter_type_id,' . FilterTypes::TAG->value,
                'array',
                Rule::exists('tags', 'id'),
            ],
            'collection_filter_types.*.type_ids' => [
                'required_if:collection_filter_types.*.filter_type_id,' . FilterTypes::TYPE->value,
                'array',
                'in:' . ProductTypes::getValues(),
            ],
            'collection_filter_types.*.sale_unit_sold' => [
                'required_if:collection_filter_types.*.filter_type_id,' . FilterTypes::SALE_UNIT_SOLD->value,
                'numeric',
                'between:1,99999999.99',
            ],
            'collection_filter_types.*.sale_amount' => [
                'required_if:collection_filter_types.*.filter_type_id,' . FilterTypes::SALE_AMOUNT->value,
                'numeric',
                'between:1,99999999.99',
            ],
            'collection_filter_types.*.order_unit_sold' => [
                'required_if:collection_filter_types.*.filter_type_id,' . FilterTypes::ORDER_UNIT_SOLD->value,
                'numeric',
                'between:1,99999999.99',
            ],
            'collection_filter_types.*.order_amount' => [
                'required_if:collection_filter_types.*.filter_type_id,' . FilterTypes::ORDER_AMOUNT->value,
                'numeric',
                'between:1,99999999.99',
            ],
            'collection_filter_types.*.condition_operator_id' => [
                'required_if:collection_filter_types.*.filter_type_id,' . FilterTypes::NAME->value . ',collection_filter_types.*.filter_type_id,' . FilterTypes::PRICE->value . ', collection_filter_types.*.filter_type_id,' . FilterTypes::CREATED_BY->value . ', collection_filter_types.*.filter_type_id,' . FilterTypes::SALE_UNIT_SOLD->value . ', collection_filter_types.*.filter_type_id,' . FilterTypes::SALE_AMOUNT->value . ', collection_filter_types.*.filter_type_id,' . FilterTypes::ORDER_UNIT_SOLD->value . ', collection_filter_types.*.filter_type_id,' . FilterTypes::ORDER_AMOUNT->value,
                'nullable',
                'integer',
                'in:' . ConditionOperatorTypes::getValues(),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        if (config('app.product_variant')) {
            return [
                'collection_filter_types.*.name.required_if' => 'Name field is required.',
                'collection_filter_types.*.created_by.required_if' => 'Created By field is required.',
                'collection_filter_types.*.price.required_if' => 'Price field is required.',
                'collection_filter_types.*.category_ids.required_if' => 'Categories field is required.',
                'collection_filter_types.*.attributes.required_if' => 'Attribute field is required.',
                'collection_filter_types.*.department_ids.required_if' => 'Departments field is required.',
                'collection_filter_types.*.brand_ids.required_if' => 'Brands field is required.',
                'collection_filter_types.*.tag_ids.required_if' => 'Tags field is required.',
                'collection_filter_types.*.type_ids.required_if' => 'Types field is required.',
                'collection_filter_types.*.sale_unit_sold.required_if' => 'Unit Sold field is required.',
                'collection_filter_types.*.sale_amount.required_if' => 'Amount field is required.',
                'collection_filter_types.*.order_unit_sold.required_if' => 'Unit Sold field is required.',
                'collection_filter_types.*.order_amount.required_if' => 'Amount field is required.',
                'collection_filter_types.*.is_available_in_pos.required_if' => 'This field is required.',
                'collection_filter_types.*.is_available_in_ecommerce.required_if' => 'This field is required.',
                'collection_filter_types.*.condition_operator_id.required_if' => 'Condition Operator field is required.',
            ];
        }

        return [
            'collection_filter_types.*.name.required_if' => 'Name field is required.',
            'collection_filter_types.*.created_by.required_if' => 'Created By field is required.',
            'collection_filter_types.*.price.required_if' => 'Price field is required.',
            'collection_filter_types.*.category_ids.required_if' => 'Categories field is required.',
            'collection_filter_types.*.season_ids.required_if' => 'Seasons field is required.',
            'collection_filter_types.*.department_ids.required_if' => 'Departments field is required.',
            'collection_filter_types.*.color_ids.required_if' => 'Colors field is required.',
            'collection_filter_types.*.size_ids.required_if' => 'Sizes field is required.',
            'collection_filter_types.*.brand_ids.required_if' => 'Brands field is required.',
            'collection_filter_types.*.style_ids.required_if' => 'Styles field is required.',
            'collection_filter_types.*.tag_ids.required_if' => 'Tags field is required.',
            'collection_filter_types.*.type_ids.required_if' => 'Types field is required.',
            'collection_filter_types.*.sale_unit_sold.required_if' => 'Unit Sold field is required.',
            'collection_filter_types.*.sale_amount.required_if' => 'Amount field is required.',
            'collection_filter_types.*.order_unit_sold.required_if' => 'Unit Sold field is required.',
            'collection_filter_types.*.order_amount.required_if' => 'Amount field is required.',
            'collection_filter_types.*.is_available_in_pos.required_if' => 'This field is required.',
            'collection_filter_types.*.is_available_in_ecommerce.required_if' => 'This field is required.',
            'collection_filter_types.*.condition_operator_id.required_if' => 'Condition Operator field is required.',
        ];
    }
}
