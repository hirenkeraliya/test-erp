<?php

declare(strict_types=1);

namespace App\Domains\AutomatedNotification\Resources;

use App\Domains\AutomatedNotificationProduct\DataPreparer\AutomatedNotificationProductDataPreparer;
use App\Domains\AutomatedNotificationStore\DataPreparer\AutomatedNotificationStoreDataPreparer;
use App\Models\AutomatedNotificationMonthDate;
use App\Models\AutomatedNotificationWeekDay;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminEditAutomatedNotificationResource extends JsonResource
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

        /** @var AutomatedNotificationMonthDate $monthly */
        $monthly = $automatedNotification->monthly;

        /** @var AutomatedNotificationWeekDay $weekly */
        $weekly = $automatedNotification->weekly;

        return [
            'id' => $automatedNotification->id,
            'type_id' => $automatedNotification->type_id,
            'name' => $automatedNotification->name,
            'description' => $automatedNotification->description,
            'timeframe_type_id' => $automatedNotification->timeframe_type_id,
            'month_dates' => $monthly->pluck('month_date')->toArray(),
            'week_days' => $weekly->pluck('week_day')->toArray(),
            'automated_email_recipients' => $automatedNotification->automatedEmailRecipients,
            'locations' => AutomatedNotificationStoreDataPreparer::prepareDataForAutomatedNotification(
                $automatedNotification->automatedNotificationStores
            ),
            'products' => AutomatedNotificationProductDataPreparer::prepareDataForAutomatedNotification(
                $automatedNotification->automatedNotificationProducts
            ),
            'low_stock_alert_threshold' => $automatedNotification->low_stock_alert_threshold,
            'sent_notification' => $automatedNotification->sent_notification,
        ];
    }
}
