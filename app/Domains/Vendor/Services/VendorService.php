<?php

declare(strict_types=1);

namespace App\Domains\Vendor\Services;

use App\Domains\Vendor\DataObjects\VendorData;

class VendorService
{
    public function getVendorData(array $vendorDetails): VendorData
    {
        return new VendorData(
            name: (string) $vendorDetails['name'],
            code: (string) $vendorDetails['code'],
            registration_number: array_key_exists(
                'registration_number',
                $vendorDetails
            ) ? (string) $vendorDetails['registration_number'] : null,
            sst_number: array_key_exists('sst_number', $vendorDetails) ? (string) $vendorDetails['sst_number'] : null,
            email: (string) $vendorDetails['email'],
            phone: (string) $vendorDetails['phone'],
            mobile: array_key_exists('mobile', $vendorDetails) ? (string) $vendorDetails['mobile'] : null,
            fax: array_key_exists('fax', $vendorDetails) ? (string) $vendorDetails['fax'] : null,
            website: array_key_exists('website', $vendorDetails) ? (string) $vendorDetails['website'] : null,
            address_line_1: (string) $vendorDetails['address_line_1'],
            address_line_2: array_key_exists(
                'address_line_2',
                $vendorDetails
            ) ? (string) $vendorDetails['address_line_2'] : null,
            city: (string) $vendorDetails['city'],
            area_code: (string) $vendorDetails['area_code'],
            is_consignment: 'Yes' === $vendorDetails['consignment'],
            commission_percentage: (int) $vendorDetails['commission_percentage'],
        );
    }
}
