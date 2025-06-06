<?php

declare(strict_types=1);

namespace App\Domains\CustomFieldValue\Resources;

use App\Domains\Attribute\Enums\FieldType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class CustomFieldValueResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     */
    public function toArray($request): array
    {
        $template = $this->resource;

        return [
            'id' => $template->id,
            'name' => $template->name,
            'attributes' => $this->mapAttributes($template->attributes),
        ];
    }

    private function mapAttributes(Collection $attributes): Collection
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
            'selected_value' => FieldType::prepareValueByFieldType($attribute->field_type, $attribute->default_value),
        ]);
    }
}
