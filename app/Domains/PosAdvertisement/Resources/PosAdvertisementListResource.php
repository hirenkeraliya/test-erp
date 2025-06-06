<?php

declare(strict_types=1);

namespace App\Domains\PosAdvertisement\Resources;

use App\Domains\PosAdvertisement\Enums\PosAdvertisementTypes;
use App\Models\PosAdvertisement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PosAdvertisementListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var PosAdvertisement $posAdvertisement */
        $posAdvertisement = $this;

        return [
            'id' => $posAdvertisement->id,
            'type' => $posAdvertisement->type_id ? [
                'id' => $posAdvertisement->type_id,
                'name' => PosAdvertisementTypes::getFormattedCaseName($posAdvertisement->type_id),
                'key' => PosAdvertisementTypes::getCaseNameByValue($posAdvertisement->type_id),
            ] : null,
            'name' => $posAdvertisement->name,
            'photo_url' => $posAdvertisement->type_id === PosAdvertisementTypes::IMAGE->value ? $posAdvertisement->getDiskBasedFirstMediaUrl(
                'photo'
            ) : null,
            'video_url' => $posAdvertisement->type_id === PosAdvertisementTypes::VIDEO->value ? $posAdvertisement->getDiskBasedFirstMediaUrl(
                'video'
            ) : null,
            'status' => (int) $posAdvertisement->status,
        ];
    }
}
