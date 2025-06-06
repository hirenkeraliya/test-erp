<?php

declare(strict_types=1);

namespace App\Domains\OrderAddress;

use App\Domains\City\CityQueries;
use App\Domains\Order\DataObjects\OrderECommerceAddressData;
use App\Domains\Order\OrderQueries;
use App\Domains\OrderAddress\DataObjects\EcommerceOrderAddressData;
use App\Models\OrderAddress;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class OrderAddressQueries
{
    public function getRelationRecordsById(int $id): OrderAddress
    {
        $orderQueries = resolve(OrderQueries::class);
        $cityQueries = resolve(CityQueries::class);

        return OrderAddress::select(
            'id',
            'order_id',
            'type_id',
            'first_name',
            'last_name',
            'phone',
            'address_line_1',
            'address_line_2',
            'country_code',
            'country_id',
            'state_id',
            'city_id',
            'city_name',
            'area_code'
        )
            ->with([
                'order:' .$orderQueries->getColumnsForAddressUpdate(),
                'city:' . $cityQueries->getBasicColumnNames(),
            ])
            ->findOrFail($id);
    }

    public function getBasicColumns(): array
    {
        return [
            'id',
            'order_id',
            'type_id',
            'first_name',
            'last_name',
            'phone',
            'address_line_1',
            'address_line_2',
            'country_code',
            'country_id',
            'state_id',
            'city_id',
            'area_code',
        ];
    }

    public function getBasicColumnsInString(): string
    {
        return implode(',', $this->getBasicColumns());
    }

    public function addNewAddress(
        array $orderBillingAddress,
        array $countryStateAndCityDetails,
        int $orderId,
        int $addressTypeId,
    ): OrderAddress {
        unset(
            $orderBillingAddress['country_id'],
            $orderBillingAddress['state_id'],
            $orderBillingAddress['city_id'],
            $orderBillingAddress['country_name'],
            $orderBillingAddress['state_name'],
            $orderBillingAddress['city_name'],
        );

        return OrderAddress::create([
            'order_id' => $orderId,
            'type_id' => $addressTypeId,
            'country_id' => $countryStateAndCityDetails['country_id'],
            'state_id' => $countryStateAndCityDetails['state_id'],
            'city_id' => $countryStateAndCityDetails['city_id'],
            'country_name' => $countryStateAndCityDetails['country_name'],
            'state_name' => $countryStateAndCityDetails['state_name'],
            'city_name' => $countryStateAndCityDetails['city_name'],
            ...$orderBillingAddress,
        ]);
    }

    public function updateOrderAddress(EcommerceOrderAddressData $ecommerceOrderAddressData, int $orderId): void
    {
        $orderAddress = OrderAddress::query()
            ->select('id')
            ->where('order_id', $orderId)
            ->where('type_id', $ecommerceOrderAddressData->type_id)
            ->first();

        if (! $orderAddress) {
            Log::channel('e_commerce')->info('Order address not found', [
                'order_id' => $orderId,
                'type_id' => $ecommerceOrderAddressData->type_id,
            ]);
            throw new RuntimeException('Order address not found for order ID: ' . $orderId);
        }

        $orderAddress->update([
            'first_name' => $ecommerceOrderAddressData->first_name,
            'last_name' => $ecommerceOrderAddressData->last_name,
            'phone' => $ecommerceOrderAddressData->phone,
            'address_line_1' => $ecommerceOrderAddressData->address_line_1,
            'address_line_2' => $ecommerceOrderAddressData->address_line_2,
            'city_name' => $ecommerceOrderAddressData->city,
            'area_code' => $ecommerceOrderAddressData->area_code,
        ]);
    }

    public function getOrderAddress(string $orderId, int $typeId): OrderAddress
    {
        $cityQueries = resolve(CityQueries::class);

        return OrderAddress::query()
            ->select([
                'id',
                'type_id',
                'order_id',
                'address_line_1',
                'address_line_2',
                'first_name',
                'last_name',
                'phone',
                'area_code',
                'city_id',
                'city_name',
            ])
            ->where([
                'type_id' => $typeId,
                'order_id' => $orderId,
            ])
            ->with('city:' . $cityQueries->getBasicColumnNames())
            ->firstOrFail();
    }

    public function updateOrderAddressECommerce(
        OrderECommerceAddressData $orderECommerceAddressData,
        int $orderAddressId
    ): void {
        $orderAddress = OrderAddress::findOrFail($orderAddressId);

        $cityQueries = resolve(CityQueries::class);
        $cityId = $cityQueries->getIdByName((string) $orderECommerceAddressData->city_name);

        $addressData = [
            'first_name' => $orderECommerceAddressData->first_name,
            'last_name' => $orderECommerceAddressData->last_name,
            'phone' => $orderECommerceAddressData->phone,
            'address_line_1' => $orderECommerceAddressData->address_line_1,
            'address_line_2' => $orderECommerceAddressData->address_line_2,
            'area_code' => $orderECommerceAddressData->area_code,
            'city_id' => $cityId,
            'city_name' => $orderECommerceAddressData->city_name,
        ];

        $orderAddress->update($addressData);
    }
}
