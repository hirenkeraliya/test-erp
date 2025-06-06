<?php

declare(strict_types=1);

namespace App\Domains\ManualNotification\Resources;

use App\Domains\ManualNotification\Enums\ManualNotificationTypes;
use App\Domains\ManualNotification\Enums\Statuses;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ManualNotificationListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $manualNotification = $this->resource;

        return [
            'id' => $manualNotification->id,
            'title' => $manualNotification->title,
            'message' => $manualNotification->message,
            'type_id' => ManualNotificationTypes::getFormattedCaseName((int) $manualNotification->type_id),
            'status' => Statuses::getFormattedCaseName((int) $manualNotification->status),
        ];
    }
}
