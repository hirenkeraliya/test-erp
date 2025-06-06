<?php

declare(strict_types=1);

namespace App\Domains\Order\Services;

use App\Domains\OrderIntegration\OrderIntegrationQueries;
use App\Models\Location;
use App\Models\Member;
use App\Models\Order;
use App\Models\OrderAddress;
use Illuminate\Support\Collection;
use Picqer\Barcode\BarcodeGeneratorPNG;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class PrintNinjaVanWayBillService
{
    public function print(array $orderIds): string
    {
        $orderIntegrationQueries = resolve(OrderIntegrationQueries::class);
        $orderIntegrations = $orderIntegrationQueries->getByOrderIdsWithStatus($orderIds);

        $orderDetails = $this->preparedData($orderIntegrations);

        return view('prints.ninjavan_way_bill', [
            'ordersDetails' => $orderDetails,
        ])->render();
    }

    /**
     * @return mixed[]
     */
    private function preparedData(Collection $orderIntegrations): array
    {
        $barcodeGeneratorPNG = resolve(BarcodeGeneratorPNG::class);

        return $orderIntegrations->map(function ($orderIntegration) use ($barcodeGeneratorPNG): array {
            /** @var Order $order */
            $order = $orderIntegration->order;

            /** @var Location $location */
            $location = $order->location;

            /** @var Member $member */
            $member = $order->member;

            /** @var OrderAddress $shippingAddress */
            $shippingAddress = $order->shippingAddress;

            $memberName = $member->first_name;
            $memberPhone = $member->mobile_number;
            $locationName = $location->name;
            $locationPhone = $location->phone;

            $fromAddress = $shippingAddress->address_line_1 . ', ' .
            ($shippingAddress->address_line_2 ? $shippingAddress->address_line_2 . ', ' : '') .
            ($shippingAddress->city ? $shippingAddress->city->name . ', ' : '') .
            ($shippingAddress->state ? $shippingAddress->state->name . ', ' : '') .
            'MY 51200';

            $toAddress = $location->address_line_1 . ', ' .
            ($location->address_line_2 ? $location->address_line_2 . ', ' : '') .
            ($location->city ? $location->city->name . ', ' : '') .
            ($location->state ? $location->state->name . ', ' : '') .
            'MY 51200';

            $trackingBarcode = base64_encode(
                $barcodeGeneratorPNG->getBarcode(
                    $orderIntegration->tracking_number,
                    $barcodeGeneratorPNG::TYPE_CODE_128,
                    2,
                    35
                )
            );

            /** @var string $trackingNumber */
            $trackingNumber = $orderIntegration->tracking_number;

            /* @phpstan-ignore-next-line */
            $qrCode = base64_encode((string) QrCode::style('round')->format('png')->size(2000)->margin(1)->generate(
                $trackingNumber
            ));

            return [
                'tracking_number' => $trackingNumber,
                'order_ref' => $order->receipt_number,
                'member_name' => $memberName,
                'member_phone' => $memberPhone,
                'location_name' => $locationName,
                'location_phone' => $locationPhone,
                'from_address' => $fromAddress,
                'to_address' => $toAddress,
                'tracking_barcode' => $trackingBarcode,
                'qr_code' => $qrCode,
            ];
        })->toArray();
    }
}
