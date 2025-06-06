<?php

declare(strict_types=1);

namespace App\Domains\Order\Services;

use App\Models\City;
use App\Models\Company;
use App\Models\Location;
use App\Models\Member;
use App\Models\Order;
use App\Models\OrderAddress;
use Nnjeim\World\Models\Country;

class OrderShipmentService
{
    public function prepareShipmentData(Order $order): array
    {
        /** @var OrderAddress $billingAddress */
        $billingAddress = $order->billingAddress;

        /** @var OrderAddress $shippingAddress */
        $shippingAddress = $order->shippingAddress;

        /** @var Location $location */
        $location = $order->getLocation();

        /** @var ?Member $member */
        $member = $order->getMember();

        /** @var Company $company */
        $company = $location->getCompany();

        return [
            'parcels' => [
                [
                    'weight' => 100,
                    'weight_unit' => 'G',
                ],
            ],
            'recipient' => $this->getPreparedRecipientAndBilling($shippingAddress, $company, $member),
            'shipper' => $this->getPreparedShipperAndReturnAddress($location, $company),
            'return_address' => $this->getPreparedShipperAndReturnAddress($location, $company),
            'billing_address' => $this->getPreparedRecipientAndBilling($billingAddress, $company, $member),
            'service' => 'Standard',
            'metadata' => [
                'is_pickup_required' => true,
                'pickup_date' => now()->addDay()->format('Y-m-d'),
                'pickup_address_id' => $order->receipt_number,
                'pickup_timeslot' => [
                    'start_time' => '09:00',
                    'end_time' => '12:00',
                    'timezone' => config('app.timezone'),
                ],
                'delivery_timeslot' => [
                    'start_time' => '09:00',
                    'end_time' => '12:00',
                    'timezone' => config('app.timezone'),
                ],
                'is_dangerous_good' => false,
                'delivery_startdate' => now()->addDay()->format('Y-m-d'),
                'requested_tracking_number' => $order->receipt_number,
                'erp_id' => $order->id,
            ],
            'carrier_ids' => ['ninja_van-ariani'],
        ];
    }

    private function getPreparedRecipientAndBilling(
        OrderAddress $address,
        Company $company,
        ?Member $member = null
    ): array {
        /** @var City $city */
        $city = $address->city->name ?? null;

        /** @var Country $countryCode */
        $countryCode = $address->country->iso2 ?? null;

        return [
            'postal_code' => $address->area_code,
            'city' => $city,
            'federal_tax_id' => null,
            'state_tax_id' => null,
            'person_name' => $address->first_name,
            'company_name' => $company->name,
            'country_code' => $countryCode,
            'email' => $member instanceof Member ? $member->email : null,
            'phone_number' => $address->phone,
            'state_code' => null,
            'residential' => false,
            'street_number' => null,
            'address_line1' => $address->address_line_1,
            'address_line2' => $address->address_line_2,
            'validate_location' => false,
        ];
    }

    private function getPreparedShipperAndReturnAddress(Location $location, Company $company): array
    {
        /** @var Country $countryCode */
        $countryCode = $location->country->iso2 ?? null;

        /** @var City $city */
        $city = $location->city;

        return [
            'postal_code' => $location->area_code,
            'city' => $city->name ?? null,
            'federal_tax_id' => null,
            'state_tax_id' => null,
            'person_name' => $location->name,
            'company_name' => $company->name,
            'country_code' => $countryCode,
            'email' => $location->email,
            'phone_number' => $location->phone,
            'state_code' => null,
            'residential' => false,
            'street_number' => null,
            'address_line1' => $location->address_line_1,
            'address_line2' => $location->address_line_2,
            'validate_location' => false,
        ];
    }
}
