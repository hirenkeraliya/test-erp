<?php

namespace App\Domains\Product\Rules;

use App\Domains\Attribute\AttributeQueries;
use App\Domains\Attribute\Enums\FieldType;
use App\Models\Attribute;
use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Translation\PotentiallyTranslatedString;
use Illuminate\Validation\Rule;

class CustomFieldValue implements ValidationRule
{
    protected array $failedRule;

    protected string $failedMessage;

    public function __construct(
        protected AttributeQueries $attributeQueries,
        protected Request $request
    ) {
    }

    /**
     * Run the validation rule.
     *
     * @param Closure(string):PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $arrayIndex = explode('.', $attribute)[1];
        $attributeIndex = explode('.', $attribute)[3];

        $customFieldValue = $this->request->custom_field_values[$arrayIndex];
        $attributeId = $customFieldValue['attributes'][$attributeIndex]['id'];

        /** @var Attribute $attributeDetails */
        $attributeDetails = $this->attributeQueries->fetchAttribute(
            $customFieldValue['id'],
            $attributeId,
            session('admin_company_id')
        );

        $rules = ['nullable'];

        if ($attributeDetails->is_required) {
            $rules = ['required'];
        }

        $customMessages = [];

        $rules[] = 'bail';
        switch ($attributeDetails->field_type) {
            case FieldType::DECIMAL:
                $rules[] = 'numeric';
                $rules[] = 'between:' . $attributeDetails->from . ',' . $attributeDetails->to;
                $rules[] = 'decimal:0,2';
                break;

            case FieldType::NUMBER:
                $rules[] = 'numeric';
                $rules[] = 'between:' . $attributeDetails->from . ',' . $attributeDetails->to;
                $rules[] = 'decimal:0';
                break;

            case FieldType::DATE:
                $rules[] = 'date';
                $rules = array_merge($rules,
                    $this->dateRules(
                        attribute: $attribute,
                        attributeDetails: $attributeDetails,
                        inputFormat: 'Y-m-d',
                        outputFormat: 'd-m-Y',
                        customMessages: $customMessages
                    ));
                break;

            case FieldType::DATETIME:
                $rules[] = 'date_format:Y-m-d H:i:s';
                $rules = array_merge($rules,
                    $this->dateRules(
                        attribute: $attribute,
                        attributeDetails: $attributeDetails,
                        inputFormat: 'Y-m-d H:i:s',
                        outputFormat: 'd-m-Y H:i:s',
                        customMessages: $customMessages
                    ));

                break;

            case FieldType::TOGGLE:
                $rules[] = 'boolean';
                break;

            case FieldType::MULTISELECT:
                $rules[] = 'array';
                $options = $attributeDetails->options;
                $rules[] = function ($attribute, array $value, $fail) use ($options): void {
                    foreach ($value as $item) {
                        if (! in_array($item, $options)) {  // @phpstan-ignore-line
                            $fail(sprintf("The selected custom field '%s' is invalid.", $item));
                        }
                    }
                };
                break;

            case FieldType::SELECT:
                $options = $attributeDetails->options ?? [];
                $rules[] = Rule::in($options);
                $customMessages[$attribute . '.in'] = 'The selected custom field is invalid';
                break;

            default:
                break;
        }

        $validator = Validator::make(
            $this->request->all(),
            [
                $attribute => $rules,
            ],
            $customMessages,
            [
                $attribute => 'custom',
            ]
        );

        if ($validator->fails()) {
            $this->failedRule = $validator->failed();
            $this->failedMessage = $validator->errors()->first();
            $fail($this->failedMessage);
        }
    }

    private function dateRules(
        string $attribute,
        Attribute $attributeDetails,
        string $inputFormat,
        string $outputFormat,
        array &$customMessages
    ): array {
        $rules = [];

        if (! empty($attributeDetails->to)) {
            $toDate = Carbon::createFromFormat($inputFormat, $attributeDetails->to);
            if (false !== $toDate) {
                $rules[] = 'before_or_equal:' . $attributeDetails->to;
                $customMessages[$attribute . '.before_or_equal'] = 'The custom field must be a date before or equal to ' . $toDate->format(
                    $outputFormat
                ) . '.';
            }
        }

        if (! empty($attributeDetails->from)) {
            $fromDate = Carbon::createFromFormat($inputFormat, $attributeDetails->from);
            if (false !== $fromDate) {
                $rules[] = 'after_or_equal:' . $attributeDetails->from;
                $customMessages[$attribute . '.after_or_equal'] = 'The custom field must be a date after or equal to ' . $fromDate->format(
                    $outputFormat
                ) . '.';
            }
        }

        return $rules;
    }
}
