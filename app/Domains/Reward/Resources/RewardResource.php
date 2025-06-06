<?php

declare(strict_types=1);

namespace App\Domains\Reward\Resources;

use App\Domains\Reward\Enums\RewardTargetTypes;
use App\Domains\Reward\Enums\RewardTypes;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RewardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $reward = $this->resource;

        return [
            'id' => $reward->id,
            'title' => $reward->title,
            'type' => RewardTypes::getFormattedCaseName($reward->type),
            'target_type' => $reward->target_type ? RewardTargetTypes::getFormattedCaseName(
                $reward->target_type
            ) : null,
            'minimum_point' => $reward->minimum_point,
            'maximum_point' => $reward->maximum_point,
            'status' => $reward->status,
        ];
    }
}
