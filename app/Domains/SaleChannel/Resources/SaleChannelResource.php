<?php

declare(strict_types=1);

namespace App\Domains\SaleChannel\Resources;

use App\Domains\Order\Enums\OrderStatus;
use App\Models\SaleChannelInventoryRollbackOrderStatus;
use App\Models\SaleChannelWebhookUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleChannelResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $saleChannel = $this->resource;

        return [
            'id' => $saleChannel->id,
            'name' => $saleChannel->getName(),
            'code' => $saleChannel->getCode(),
            'company_id' => $saleChannel->getCompanyId(),
            'default_location_id' => $saleChannel->getDefaultLocationId(),
            'type_id' => $saleChannel->type_id->value,
            'url' => $saleChannel->getUrl(),
            'secret' => $saleChannel->getSecret(),
            'display_variants' => $saleChannel->display_variants,
            'display_dynamic_menus' => $saleChannel->display_dynamic_menus,
            'inventory_deduct_order_status' => $saleChannel->inventory_deduct_order_status,
            'inventory_rollback_order_status' => $saleChannel->saleChannelInventoryRollbackOrderStatus->map(
                fn (SaleChannelInventoryRollbackOrderStatus $saleChannelInventoryRollbackOrderStatus): array => [
                    'id' => $saleChannelInventoryRollbackOrderStatus->order_status,
                    'name' => $saleChannelInventoryRollbackOrderStatus->order_status ? OrderStatus::getFormattedCaseName(
                        $saleChannelInventoryRollbackOrderStatus->order_status
                    ) : null,
                ]
            )->toArray(),
            'webhook_urls' => $saleChannel->saleChannelWebhookUrls->map(
                fn (SaleChannelWebhookUrl $saleChannelWebhookUrl): array => [
                    'webhook_url_type_id' => $saleChannelWebhookUrl->webhook_url_type_id,
                    'url' => $saleChannelWebhookUrl->url,
                ]
            )->toArray(),
            'round_off_configuration' => $saleChannel->round_off_configuration,
        ];
    }
}
