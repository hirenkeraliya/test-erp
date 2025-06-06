<?php

declare(strict_types=1);

namespace App\Domains\Attribute\DataObjects;

use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

class AttributeOldData extends Data
{
    public function __construct(
        public int $attribute_id,
        public bool $existing_attribute,
    ) {
    }

    public static function rules(): array
    {
        return [
            'existing_attribute' => ['required', 'boolean'],
            'attribute_id' => ['required', 'integer', Rule::exists('attributes', 'id')],
        ];
    }
}
