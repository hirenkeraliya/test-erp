<?php

declare(strict_types=1);

namespace App\Domains\AutomatedNotification\Resources;

use App\Domains\AutomatedNotification\Enums\AutomatedNotificationTimeframeTypes;
use App\Domains\AutomatedNotification\Enums\AutomatedNotificationTypes;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminAutomatedNotificationListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $automatedNotification = $this->resource;

        return [
            'id' => $automatedNotification->id,
            'type' => AutomatedNotificationTypes::getFormattedCaseName($automatedNotification->type_id),
            'name' => $automatedNotification->name,
            'description' => $automatedNotification->description,
            'timeframe_type' => $automatedNotification->timeframe_type_id ? AutomatedNotificationTimeframeTypes::getFormattedCaseName(
                $automatedNotification->timeframe_type_id
            ) : 'N/A',
        ];
    }
}
