<?php

declare(strict_types=1);

namespace App\Domains\Pos\Resources;

use App\Models\Brand;
use App\Models\Color;
use App\Models\ColorGroup;
use App\Models\Season;
use App\Models\Size;
use App\Models\Style;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PosCommonResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var Color|Season|Size|Brand|Style|ColorGroup $data */
        $data = $this;

        /** @var Carbon $updatedAt */
        $updatedAt = $data->updated_at;

        /** @var Carbon $createdAt */
        $createdAt = $data->created_at;

        return [
            'id' => $data->id,
            'name' => $data->name,
            'code' => $data->code,
            'created_at' => $createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
