<?php

declare(strict_types=1);

namespace App\Domains\UnitOfMeasure\Resources;

use App\Models\UnitOfMeasure;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class PosUnitOfMeasureListResource extends JsonResource
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

        /** @var Collection $unitOfMeasureDerivatives */
        $unitOfMeasureDerivatives = $unitOfMeasure->derivatives;

        return [
            'id' => $unitOfMeasure->id,
            'name' => $unitOfMeasure->getName(),
            'allow_decimal_qty' => $unitOfMeasure->allow_decimal_qty,
            'derivatives' => $unitOfMeasureDerivatives->map(fn ($unitOfMeasureDerivative): array => [
                'id' => $unitOfMeasureDerivative->id,
                'name' => $unitOfMeasureDerivative->name,
                'ratio' => (float) $unitOfMeasureDerivative->ratio,
            ]),
        ];
    }
}
