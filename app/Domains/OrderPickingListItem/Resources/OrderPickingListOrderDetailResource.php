<?php

declare(strict_types=1);

namespace App\Domains\OrderPickingListItem\Resources;

use App\Models\Member;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class OrderPickingListOrderDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $orderPickingListItem = $this->resource;

        /** @var Order $order */
        $order = $orderPickingListItem->order;

        /** @var ?Member $member */
        $member = $order->getMember();

        return [
            'id' => $order->id,
            'receipt_number' => $order->receipt_number,
            'bill_reference_number' => $order->bill_reference_number ?? 'N/A',
            'type_id' => $order->type_id->value,
            'type' => Str::of($order->type_id->name)->title()->replace('_', ' ')->value(),
            'channel' => Str::of($order->channel_id->name)->title()->replace('_', ' ')->value(),
            'member' => $member instanceof Member ? $member->getFullName() : 'Walk In Member',
            'status' => $order->status?->name,
        ];
    }
}
