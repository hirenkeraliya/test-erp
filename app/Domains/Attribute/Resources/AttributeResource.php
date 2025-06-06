<?php

declare(strict_types=1);

namespace App\Domains\Attribute\Resources;

use App\Domains\Attribute\Enums\FieldType;
use App\Models\Attribute;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttributeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Attribute $attribute */
        $attribute = $this;

        return [
            'id' => $attribute->id,
            'name' => $attribute->name,
            'description' => $attribute->description,
            'field_type' => $attribute->field_type,
            'is_required' => $attribute->is_required,
            'default_value' => FieldType::prepareValueByFieldType($attribute->field_type, $attribute->default_value),
            $this->mergeWhen(in_array($attribute->field_type, FieldType::allowFromToFunctionalityFields()), [
                'from' => $attribute->from,
                'to' => $attribute->to,
            ]),
            $this->mergeWhen(in_array($attribute->field_type, FieldType::selections()), [
                'options' => $attribute->options,
            ]),
        ];
    }
}
