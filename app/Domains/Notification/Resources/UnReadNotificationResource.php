<?php

declare(strict_types=1);

namespace App\Domains\Notification\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UnReadNotificationResource extends JsonResource
{
    public function toArray($request): array
    {
        $notification = $this->resource;

        return [
            'id' => $notification->id,
            'title' => $notification->title,
            'message' => $notification->text_message,
            'web_message' => $notification->message,
            'metadata' => $notification->payload,
            'created_at' => $notification->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
