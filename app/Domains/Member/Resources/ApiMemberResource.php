<?php

declare(strict_types=1);

namespace App\Domains\Member\Resources;

use App\Domains\Member\Enums\Types;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApiMemberResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var Member $member */
        $member = $this;

        return [
            'id' => $member->getKey(),
            'first_name' => $member->first_name,
            'last_name' => $member->last_name,
            'mobile_number' => $member->mobile_number,
            'card_number' => $member->card_number,
            'type' => $member->type_id ? Types::getFormattedCaseName($member->type_id) : '',
        ];
    }
}
