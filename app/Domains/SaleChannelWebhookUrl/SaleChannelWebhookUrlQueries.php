<?php

declare(strict_types=1);

namespace App\Domains\SaleChannelWebhookUrl;

use App\Models\SaleChannel;
use App\Models\SaleChannelWebhookUrl;

class SaleChannelWebhookUrlQueries
{
    public function addNew(array $orderStatusData): SaleChannelWebhookUrl
    {
        return SaleChannelWebhookUrl::create($orderStatusData);
    }

    public function deleteSaleChannelWebhookUrl(SaleChannel $saleChannel): void
    {
        $saleChannel->saleChannelWebhookUrls()->delete();
    }

    public function getBasicColumns(): array
    {
        return ['id', 'sale_channel_id', 'webhook_url_type_id', 'url'];
    }

    public function getBasicColumnsInString(): string
    {
        return implode(',', $this->getBasicColumns());
    }
}
