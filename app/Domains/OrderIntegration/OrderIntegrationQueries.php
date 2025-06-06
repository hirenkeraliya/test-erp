<?php

declare(strict_types=1);

namespace App\Domains\OrderIntegration;

use App\Domains\City\CityQueries;
use App\Domains\Courier\CourierQueries;
use App\Domains\CourierWebhookUrl\CourierWebhookUrlQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Member\MemberQueries;
use App\Domains\Order\OrderQueries;
use App\Domains\OrderAddress\OrderAddressQueries;
use App\Domains\OrderIntegration\Enums\IntegrationStatuses;
use App\Models\OrderIntegration;
use Illuminate\Support\Collection;

class OrderIntegrationQueries
{
    public function getByIdAndOrderId(int $id, int $orderId): OrderIntegration
    {
        $courierQueries = resolve(CourierQueries::class);
        $courierWebhookUrlQueries = resolve(CourierWebhookUrlQueries::class);

        return OrderIntegration::select('id', 'order_id', 'courier_id', 'status', 'tracking_number', 'response')
            ->with([
                'courier:' . $courierQueries->getBasicColumnsInString(),
                'courier.courierWebhookUrls:' . $courierWebhookUrlQueries->getBasicColumnsInString(),
            ])
            ->where('order_id', $orderId)
            ->findOrFail($id);
    }

    public function updateStatusAndTrackingNumber(
        OrderIntegration $orderIntegration,
        int $status,
        array $response = []
    ): OrderIntegration {
        $orderIntegration->status = $status;
        if ([] !== $response && array_key_exists('tracking_number', $response)) {
            $orderIntegration->tracking_number = $response['tracking_number'];
        }

        $orderIntegration->response = json_encode(
            array_merge($orderIntegration->response ? json_decode($orderIntegration->response, true) : [], $response),
            JSON_THROW_ON_ERROR
        );
        $orderIntegration->save();

        return $orderIntegration;
    }

    public function addNew(int $orderId, int $courierId): int
    {
        return OrderIntegration::firstOrCreate([
            'order_id' => $orderId,
        ], [
            'courier_id' => $courierId,
            'status' => IntegrationStatuses::CREATE_ORDER->value,
        ])->id;
    }

    public function getByOrderIdsWithStatus(array $orderIds): Collection
    {
        $orderQueries = resolve(OrderQueries::class);
        $cityQueries = resolve(CityQueries::class);
        $orderAddressQueries = resolve(OrderAddressQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $memberQueries = resolve(MemberQueries::class);

        return OrderIntegration::select('id', 'order_id', 'tracking_number')
            ->with([
                'order:' . $orderQueries->getBasicColumnNames(),
                'order.shippingAddress:' . $orderAddressQueries->getBasicColumnsInString(),
                'order.shippingAddress.city:' . $cityQueries->getBasicColumnNames(),
                'order.shippingAddress.country:id,name,iso2',
                'order.shippingAddress.state:id,name',
                'order.location:' . $locationQueries->getColumnsForShipment(),
                'order.location.country:id,name,iso2',
                'order.location.city:' . $cityQueries->getBasicColumnNames(),
                'order.location.state:id,name',
                'order.member:' . $memberQueries->getBasicColumnNames(),
            ])
            ->whereIntegerInRaw('order_id', $orderIds)
            ->where('status', IntegrationStatuses::GENERATE_WAY_BILL)
            ->get();
    }
}
