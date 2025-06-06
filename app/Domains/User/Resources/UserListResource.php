<?php

declare(strict_types=1);

namespace App\Domains\User\Resources;

use App\Domains\User\Enums\UserTypes;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $user = $this->resource;

        return [
            'id' => $user->id,
            'username' => $user->username,
            'type' => UserTypes::getFormattedCaseName($user->type_id),
            'employee' => $user->employee,
        ];
    }
}
