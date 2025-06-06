<?php

declare(strict_types=1);

namespace App\Domains\Attribute\DataObjects;

use App\Domains\Attribute\Enums\FieldType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

class AttributeData extends Data
{
    public function __construct(
        public string $name,
        public ?string $description,
        public int $field_type,
        public ?array $options,
        public ?string $from,
        public ?string $to,
        public string|array|bool|null $default_value,
        public bool $is_required,
    ) {
    }

    public static function rules(Request $request): array
    {
        $validationRules = [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'field_type' => ['required', Rule::in(FieldType::getArrayValues())],
            'options' => [
                'sometimes',
                'nullable',
                'required_if:field_type,' . FieldType::SELECT->value,
                'required_if:field_type,' . FieldType::MULTISELECT->value,
                'array',
            ],
            'options.*' => ['required', 'distinct', 'max:255'],
            'from' => ['nullable'],
            'to' => ['nullable'],
            'default_value' => ['sometimes', 'nullable'],
            'is_required' => ['required', 'boolean'],
        ];

        // Field type specific validation
        if ($request->field_type) {
            $fieldTypeValidationRules = self::getFieldTypeValidationRules($request);
            $validationRules = array_merge($validationRules, $fieldTypeValidationRules);
        }

        return $validationRules;
    }

    /**
     * Get validation rules based on field type.
     */
    private static function getFieldTypeValidationRules(Request $request): array
    {
        $fieldType = FieldType::getCaseNameWithValue($request->field_type);
        $fieldTypeValidationRules = [];

        switch ($fieldType) {
            case FieldType::DECIMAL:
                $fieldTypeValidationRules = [
                    'from' => ['required', 'numeric', 'decimal:0,2'],
                    'to' => ['required', 'numeric', 'decimal:0,2', 'gt:from'],
                    'default_value' => [
                        'bail',
                        'nullable',
                        'numeric',
                        'between:' . $request->from . ',' . $request->to,
                        'decimal:0,2',
                    ],
                ];
                break;

            case FieldType::NUMBER:
                $fieldTypeValidationRules = [
                    'from' => ['required', 'numeric', 'decimal:0'],
                    'to' => ['required', 'numeric', 'decimal:0', 'gt:from'],
                    'default_value' => [
                        'bail',
                        'nullable',
                        'numeric',
                        'between:' . $request->from . ',' . $request->to,
                        'decimal:0',
                    ],
                ];
                break;

            case FieldType::DATE:
                $fieldTypeValidationRules = [
                    'from' => ['required', 'date'],
                    'to' => ['required', 'date', 'after:from'],
                    'default_value' => ['nullable', 'date', 'after_or_equal:from', 'before_or_equal:to'],
                ];
                break;

            case FieldType::DATETIME:
                $fieldTypeValidationRules = [
                    'from' => ['required', 'date', 'date_format:Y-m-d H:i:s'],
                    'to' => ['required', 'date', 'date_format:Y-m-d H:i:s', 'after:from'],
                    'default_value' => [
                        'nullable',
                        'date',
                        'date_format:Y-m-d H:i:s',
                        'after_or_equal:from',
                        'before_or_equal:to',
                    ],
                ];
                break;

            case FieldType::TOGGLE:
                $fieldTypeValidationRules = [
                    'default_value' => ['required', 'boolean'],
                ];
                break;

            case FieldType::MULTISELECT:
                $fieldTypeValidationRules = [
                    'default_value.*' => ['nullable', 'in_array:options.*'],
                ];
                break;

            case FieldType::SELECT:
                $fieldTypeValidationRules = [
                    'default_value' => ['nullable', 'in_array:options.*'],
                ];
                break;

            default:
                break;
        }

        return $fieldTypeValidationRules;
    }

    /**
     * @return array<string, string>
     */
    public static function messages(Request $request): array
    {
        return [
            'to.after' => 'The To field must be after From field',
            'to.gt' => 'The To field must be more than From field.',
            'default_value.in_array' => 'The default value field must exist in options.',
            'default_value.*.in_array' => 'The default value(s) field must exist in options.',
            'default_value.between' => 'The default value field must be between From & To.',
            'default_value.after_or_equal' => 'The default value field must be between From & To.',
            'default_value.before_or_equal' => 'The default value field must be between From & To.',
            'options.*.required' => 'This option field is required.',
            'options.*.distinct' => 'This option field has duplicate value.',
        ];
    }
}
