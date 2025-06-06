<?php

declare(strict_types=1);

namespace App\Domains\CourierWebhookUrl;

use App\Models\Courier;
use App\Models\CourierWebhookUrl;

class CourierWebhookUrlQueries
{
    public function addNew(array $webUrlData): CourierWebhookUrl
    {
        return CourierWebhookUrl::create($webUrlData);
    }

    public function deleteCourierWebhookUrl(Courier $courier): void
    {
        $courier->courierWebhookUrls()->delete();
    }

    public function getBasicColumns(): array
    {
        return ['id', 'courier_id', 'webhook_url_type_id', 'url'];
    }

    public function getBasicColumnsInString(): string
    {
        return implode(',', $this->getBasicColumns());
    }
}
