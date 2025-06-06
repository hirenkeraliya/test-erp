<?php

declare(strict_types=1);

namespace App\Domains\Department\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentWebhookResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $department = $this->resource;

        /** @var Carbon $updatedAt */
        $updatedAt = $department->updated_at;

        return [
            'id' => $department->id,
            'name' => $department->name,
            'code' => $department->code,
            'updated_at' => $updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
