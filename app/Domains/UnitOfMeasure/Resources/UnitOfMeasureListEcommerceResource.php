<?php

declare(strict_types=1);

namespace App\Domains\UnitOfMeasure\Resources;

use App\Models\UnitOfMeasure;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UnitOfMeasureListEcommerceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var UnitOfMeasure $unitOfMeasure */
        $unitOfMeasure = $this;

        return [
            'id' => $unitOfMeasure->id,
            'name' => $unitOfMeasure->getName(),
        ];
    }
}
