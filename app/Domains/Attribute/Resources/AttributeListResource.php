<?php

declare(strict_types=1);

namespace App\Domains\Attribute\Resources;

use App\Domains\Attribute\Enums\FieldType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttributeListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        $attribute = $this->resource;

        return [
            'id' => $attribute->id,
            'name' => $attribute->name,
            'field_type' => FieldType::getFormattedCaseName($attribute->field_type->value),
        ];
    }
}
